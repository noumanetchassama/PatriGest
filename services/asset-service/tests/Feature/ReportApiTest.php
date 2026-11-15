<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\AssetIncident;
use App\Models\AssetInventory;
use App\Models\AssetMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    private function fakeAuth(array $permissions): void
    {
        Http::fake([
            '*/api/v1/auth/me' => Http::response([
                'data' => [
                    'id' => 1,
                    'name' => 'Reporter',
                    'email' => 'r@test.local',
                    'permissions' => $permissions,
                ],
            ], 200),
        ]);
    }

    public function test_overview_aggregates_all_domains(): void
    {
        $this->fakeAuth(['view-reports']);

        Asset::create(['reference' => 'PG-MOB-0001', 'type' => 'mobilier', 'designation' => 'A', 'status' => 'actif', 'value' => 100000]);
        Asset::create(['reference' => 'PG-VEH-0001', 'type' => 'vehicule', 'designation' => 'B', 'status' => 'actif', 'value' => 5000000]);
        Asset::create(['reference' => 'PG-IMM-0001', 'type' => 'immobilier', 'designation' => 'C', 'status' => 'brouillon', 'value' => 20000000]);

        AssetMaintenance::create([
            'asset_id' => 1, 'type' => 'preventive', 'description' => 'X',
            'status' => 'en_cours', 'cost' => 50000,
        ]);
        AssetMaintenance::create([
            'asset_id' => 1, 'type' => 'corrective', 'description' => 'Y',
            'status' => 'terminee', 'cost' => 75000,
        ]);

        AssetIncident::create([
            'asset_id' => 2, 'type' => 'dommage', 'description' => 'Z',
            'occurred_at' => now(), 'severity' => 'majeure', 'status' => 'ouvert',
        ]);

        $inventory = AssetInventory::create(['name' => 'Inv', 'status' => 'clos']);
        $inventory->items()->create(['asset_id' => 1, 'status' => 'absent']);

        AssetAssignment::create([
            'asset_id' => 1, 'custodian_user_id' => 9, 'requested_by' => 2,
            'status' => 'en_attente',
        ]);

        $response = $this->getJson('/api/v1/reports/overview', [
            'Authorization' => 'Bearer test-token',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.assets.total', 3)
            ->assertJsonPath('data.assets.total_value', 25100000)
            ->assertJsonPath('data.assets.by_type.mobilier', 1)
            ->assertJsonPath('data.assets.by_status.actif', 2)
            ->assertJsonPath('data.maintenances.en_cours', 1)
            ->assertJsonPath('data.maintenances.cout_total_terminées', 75000)
            ->assertJsonPath('data.incidents.ouverts', 1)
            ->assertJsonPath('data.incidents.ouverts_par_severite.majeure', 1)
            ->assertJsonPath('data.inventories.clos', 1)
            ->assertJsonPath('data.inventories.biens_absents', 1)
            ->assertJsonPath('data.workflows.assignments_pending', 1);
    }

    public function test_requires_view_reports_permission(): void
    {
        $this->fakeAuth(['view-assets']);

        $this->getJson('/api/v1/reports/overview', [
            'Authorization' => 'Bearer test-token',
        ])->assertForbidden();
    }

    public function test_guest_is_rejected(): void
    {
        $this->getJson('/api/v1/reports/overview')->assertUnauthorized();
    }
}
