<?php

namespace Tests\Feature;

use App\Models\Structure;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DemoDataSeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@mfb.patri.test')->firstOrFail();
    }

    private function agent(): User
    {
        return User::where('email', 'm.foli@mfb.patri.test')->firstOrFail();
    }

    // ---------- Utilisateurs ----------

    public function test_admin_can_list_users(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_user_without_manage_users_cannot_list_users(): void
    {
        $this->actingAs($this->agent(), 'sanctum')
            ->getJson('/api/v1/users')
            ->assertForbidden();
    }

    public function test_admin_can_create_user_with_role_and_structure(): void
    {
        $structure = Structure::where('code', 'SDA')->firstOrFail();

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Nouvel Agent',
                'username' => 'nouvel.agent',
                'email' => 'nouvel.agent@mfb.patri.test',
                'password' => 'MotDePasse2026',
                'roles' => ['agent-parc-auto'],
                'structures' => [['id' => $structure->id, 'is_primary' => true]],
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.roles.0', 'agent-parc-auto');

        $this->assertDatabaseHas('users', ['email' => 'nouvel.agent@mfb.patri.test']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created']);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/users/{$admin->id}")
            ->assertUnprocessable();
    }

    public function test_user_creation_validates_unique_email(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'Doublon',
                'email' => 'admin@mfb.patri.test',
                'password' => 'MotDePasse2026',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    // ---------- Rôles ----------

    public function test_admin_can_create_role_with_permissions(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/roles', [
                'name' => 'role-test',
                'permissions' => ['view-assets', 'view-reports'],
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('roles', ['name' => 'role-test']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'role.created']);
    }

    public function test_role_with_users_cannot_be_deleted(): void
    {
        $role = \Spatie\Permission\Models\Role::findByName('agent-parc-auto');

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/v1/roles/{$role->id}")
            ->assertUnprocessable();
    }

    // ---------- Structures ----------

    public function test_admin_can_create_and_list_structures(): void
    {
        $mfb = Structure::where('code', 'MFB')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/structures', [
                'parent_id' => $mfb->id,
                'type' => 'service',
                'name' => 'Service Test',
                'code' => 'STEST',
            ])
            ->assertCreated();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/structures?type=service')
            ->assertOk();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/structures-tree')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_structure_rejects_invalid_type(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/structures', [
                'type' => 'ministere',
                'name' => 'Invalide',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    // ---------- Audit ----------

    public function test_admin_technique_can_view_audit_logs(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/audit-logs')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_agent_cannot_view_audit_logs(): void
    {
        $this->actingAs($this->agent(), 'sanctum')
            ->getJson('/api/v1/audit-logs')
            ->assertForbidden();
    }
}
