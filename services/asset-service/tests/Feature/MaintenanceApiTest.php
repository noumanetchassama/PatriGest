<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetMaintenance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MaintenanceApiTest extends TestCase
{
    use RefreshDatabase;

    private function fakeAuth(array $permissions, int $userId = 1): void
    {
        Http::fake([
            '*/api/v1/auth/me' => Http::response([
                'data' => [
                    'id' => $userId,
                    'name' => 'User '.$userId,
                    'email' => "user{$userId}@test.local",
                    'roles' => ['responsable-patrimoine'],
                    'permissions' => $permissions,
                ],
            ], 200),
        ]);
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer test-token'];
    }

    private function makeAsset(array $attributes = []): Asset
    {
        static $i = 0;
        $i++;

        return Asset::create(array_merge([
            'reference' => "PG-MOB-{$i}",
            'type' => Asset::TYPE_MOBILIER,
            'designation' => 'Chaise de bureau',
            'status' => Asset::STATUS_ACTIF,
        ], $attributes));
    }

    private function makeMaintenance(Asset $asset, array $attributes = []): AssetMaintenance
    {
        return AssetMaintenance::create(array_merge([
            'asset_id' => $asset->id,
            'type' => AssetMaintenance::TYPE_PREVENTIVE,
            'description' => 'Vidange annuelle',
            'status' => AssetMaintenance::STATUS_PLANIFIEE,
        ], $attributes));
    }

    public function test_creates_maintenance_planifiee(): void
    {
        $this->fakeAuth(['view-assets', 'manage-maintenance']);
        $asset = $this->makeAsset();

        $response = $this->postJson(
            "/api/v1/assets/{$asset->id}/maintenances",
            ['type' => 'preventive', 'description' => 'Révision complète', 'scheduled_date' => '2026-12-01'],
            $this->headers()
        );

        $response->assertCreated()
            ->assertJsonPath('data.type', 'preventive')
            ->assertJsonPath('data.status', 'planifiee')
            ->assertJsonPath('data.asset_reference', 'PG-MOB-1');
    }

    public function test_index_filters_by_asset_and_status(): void
    {
        $this->fakeAuth(['view-assets']);
        $asset = $this->makeAsset();
        $this->makeMaintenance($asset);
        $this->makeMaintenance($this->makeAsset(), ['status' => 'terminee']);

        $response = $this->getJson(
            "/api/v1/maintenances?asset_id={$asset->id}&status=planifiee",
            $this->headers()
        );

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_viewer_cannot_create_maintenance(): void
    {
        $this->fakeAuth(['view-assets']);
        $asset = $this->makeAsset();

        $this->postJson(
            "/api/v1/assets/{$asset->id}/maintenances",
            ['type' => 'preventive', 'description' => 'Test'],
            $this->headers()
        )->assertForbidden();
    }

    public function test_start_sets_asset_status_to_maintenance(): void
    {
        $this->fakeAuth(['view-assets', 'manage-maintenance']);
        $asset = $this->makeAsset();
        $maintenance = $this->makeMaintenance($asset);

        $this->postJson(
            "/api/v1/maintenances/{$maintenance->id}/start",
            [],
            $this->headers()
        )->assertOk()->assertJsonPath('data.status', 'en_cours');

        $this->assertSame(Asset::STATUS_MAINTENANCE, $asset->fresh()->status);
        $this->assertSame(Asset::STATUS_ACTIF, $maintenance->fresh()->previous_asset_status);
    }

    public function test_complete_restores_previous_asset_status(): void
    {
        $this->fakeAuth(['view-assets', 'manage-maintenance']);
        $asset = $this->makeAsset(['status' => Asset::STATUS_HORS_SERVICE]);
        $maintenance = $this->makeMaintenance($asset);

        $this->postJson("/api/v1/maintenances/{$maintenance->id}/start", [], $this->headers());
        $this->postJson(
            "/api/v1/maintenances/{$maintenance->id}/complete",
            ['cost' => 45000, 'notes' => 'OK'],
            $this->headers()
        )->assertOk()->assertJsonPath('data.status', 'terminee');

        $this->assertSame(Asset::STATUS_HORS_SERVICE, $asset->fresh()->status);
        $this->assertSame('45000.00', $maintenance->fresh()->cost);
    }

    public function test_cannot_complete_without_starting(): void
    {
        $this->fakeAuth(['view-assets', 'manage-maintenance']);
        $asset = $this->makeAsset();
        $maintenance = $this->makeMaintenance($asset);

        $this->postJson(
            "/api/v1/maintenances/{$maintenance->id}/complete",
            [],
            $this->headers()
        )->assertStatus(422);

        $this->assertSame(Asset::STATUS_ACTIF, $asset->fresh()->status);
    }

    public function test_cancel_in_progress_restores_asset_status(): void
    {
        $this->fakeAuth(['view-assets', 'manage-maintenance']);
        $asset = $this->makeAsset();
        $maintenance = $this->makeMaintenance($asset);

        $this->postJson("/api/v1/maintenances/{$maintenance->id}/start", [], $this->headers());
        $this->postJson(
            "/api/v1/maintenances/{$maintenance->id}/cancel",
            [],
            $this->headers()
        )->assertOk()->assertJsonPath('data.status', 'annulee');

        $this->assertSame(Asset::STATUS_ACTIF, $asset->fresh()->status);
    }

    public function test_started_maintenance_cannot_be_edited(): void
    {
        $this->fakeAuth(['view-assets', 'manage-maintenance']);
        $asset = $this->makeAsset();
        $maintenance = $this->makeMaintenance($asset);

        $this->postJson("/api/v1/maintenances/{$maintenance->id}/start", [], $this->headers());

        $this->putJson(
            "/api/v1/maintenances/{$maintenance->id}",
            ['description' => 'Nouvelle description'],
            $this->headers()
        )->assertStatus(422);
    }

    public function test_guest_is_rejected(): void
    {
        $this->getJson('/api/v1/maintenances')->assertUnauthorized();
    }
}
