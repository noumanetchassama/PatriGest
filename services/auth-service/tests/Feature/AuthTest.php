<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DemoDataSeeder::class);
    }

    public function test_login_with_email_returns_token_and_user(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'admin@mfb.patri.test',
            'password' => DemoDataSeeder::DEMO_PASSWORD,
            'device_name' => 'phpunit',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'roles']]);

        $this->assertDatabaseHas('login_logs', ['login' => 'admin@mfb.patri.test', 'success' => true]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login']);
    }

    public function test_login_with_username_works(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'kossi.mensah',
            'password' => DemoDataSeeder::DEMO_PASSWORD,
        ]);

        $response->assertOk()->assertJsonStructure(['token']);
    }

    public function test_login_with_wrong_password_is_rejected_and_logged(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'admin@mfb.patri.test',
            'password' => 'mauvais-mot-de-passe',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('login');
        $this->assertDatabaseHas('login_logs', ['success' => false]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::where('email', 'admin@mfb.patri.test')->firstOrFail();
        $user->update(['is_active' => false]);

        $this->postJson('/api/v1/auth/login', [
            'login' => 'admin@mfb.patri.test',
            'password' => DemoDataSeeder::DEMO_PASSWORD,
        ])->assertUnprocessable();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::where('email', 'a.lawson@mfb.patri.test')->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'a.lawson@mfb.patri.test')
            ->assertJsonPath('data.roles.0', 'responsable-patrimoine');
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::where('email', 'admin@mfb.patri.test')->firstOrFail();

        $login = $this->postJson('/api/v1/auth/login', [
            'login' => 'admin@mfb.patri.test',
            'password' => DemoDataSeeder::DEMO_PASSWORD,
        ]);

        $token = $login->json('token');

        $tokenId = (int) explode('|', $token)[0];

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);

        // Le guard Sanctum met l'utilisateur en cache dans un même test :
        // on le réinitialise pour simuler une nouvelle requête.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_guests_cannot_access_protected_routes(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->getJson('/api/v1/users')->assertUnauthorized();
    }
}
