<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DemoDataSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_redirect_behind_https_proxy_preserves_public_scheme_and_host(): void
    {
        $this->withHeaders([
            'X-Forwarded-For' => '203.0.113.10',
            'X-Forwarded-Host' => 'patrigest.onrender.com',
            'X-Forwarded-Port' => '443',
            'X-Forwarded-Proto' => 'https',
        ])->get('/')
            ->assertRedirect('https://patrigest.onrender.com/login');
    }

    public function test_login_page_is_displayed(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertCookie(config('session.cookie'));
    }

    public function test_user_can_login_via_web_form(): void
    {
        $response = $this->post('/login', [
            'login' => 'admin@mfb.patri.test',
            'password' => DemoDataSeeder::DEMO_PASSWORD,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('login_logs', ['success' => true]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => User::where('email', 'admin@mfb.patri.test')->value('id'),
            'name' => 'web-dashboard',
        ]);
    }

    public function test_user_can_login_with_username(): void
    {
        $this->post('/login', [
            'login' => 'kossi.mensah',
            'password' => DemoDataSeeder::DEMO_PASSWORD,
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_wrong_credentials_show_error(): void
    {
        $this->from('/login')->post('/login', [
            'login' => 'admin@mfb.patri.test',
            'password' => 'faux',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login_via_web(): void
    {
        User::where('email', 'admin@mfb.patri.test')->firstOrFail()
            ->update(['is_active' => false]);

        $this->from('/login')->post('/login', [
            'login' => 'admin@mfb.patri.test',
            'password' => DemoDataSeeder::DEMO_PASSWORD,
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::where('email', 'admin@mfb.patri.test')->firstOrFail();
        $this->post('/login', [
            'login' => 'admin@mfb.patri.test',
            'password' => DemoDataSeeder::DEMO_PASSWORD,
        ]);
        $tokenId = session('asset_api_token_id');

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_dashboard_displays_asset_statistics_from_the_asset_service(): void
    {
        Http::fake([
            '*/api/v1/assets/stats' => Http::response([
                'data' => [
                    'total' => 3,
                    'total_value' => 575000,
                    'by_type' => ['mobilier' => 2, 'immobilier' => 1],
                    'by_status' => ['actif' => 2, 'brouillon' => 1],
                ],
            ]),
            '*/api/v1/reports/overview' => Http::response([
                'data' => [
                    'assets' => [
                        'total' => 3,
                        'total_value' => 575000,
                        'by_type' => ['mobilier' => 2, 'immobilier' => 1],
                        'by_status' => ['actif' => 2, 'brouillon' => 1],
                    ],
                    'workflows' => ['assignments_pending' => 1, 'transfers_pending' => 0, 'submissions_pending' => 0],
                    'maintenances' => ['en_cours' => 0, 'planifiees' => 0, 'terminees' => 0, 'cout_total_terminées' => 0],
                    'incidents' => ['ouverts' => 0, 'ouverts_par_severite' => []],
                    'inventories' => ['en_cours' => 0, 'clos' => 0, 'biens_absents' => 0],
                    'documents_total' => 0,
                ],
            ]),
            '*/api/v1/assignments*' => Http::response(['data' => []]),
            '*/api/v1/transfers*' => Http::response(['data' => []]),
            '*/api/v1/asset-submissions*' => Http::response(['data' => []]),
        ]);

        $this->post('/login', [
            'login' => 'admin@mfb.patri.test',
            'password' => DemoDataSeeder::DEMO_PASSWORD,
        ]);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Total des biens')
            ->assertSee('Mobiliers')
            ->assertSee('575 000')
            ->assertSee('Répartition par catégorie')
            ->assertSee('État général des biens');

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization'));
    }

    public function test_dashboard_reports_asset_service_unavailability(): void
    {
        Http::fake([
            '*/api/v1/assets/stats' => Http::response(['message' => 'Unavailable'], 503),
            '*/api/v1/reports/overview' => Http::response(['message' => 'Unavailable'], 503),
            '*/api/v1/assignments*' => Http::response(['message' => 'Unavailable'], 503),
            '*/api/v1/transfers*' => Http::response(['message' => 'Unavailable'], 503),
            '*/api/v1/asset-submissions*' => Http::response(['message' => 'Unavailable'], 503),
        ]);

        $this->post('/login', [
            'login' => 'admin@mfb.patri.test',
            'password' => DemoDataSeeder::DEMO_PASSWORD,
        ]);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Le service patrimoine est momentanément indisponible.');
    }

    public function test_authenticated_user_is_redirected_from_login_page(): void
    {
        $user = User::where('email', 'admin@mfb.patri.test')->firstOrFail();

        $this->actingAs($user)->get('/login')->assertRedirect(route('dashboard'));
    }
}
