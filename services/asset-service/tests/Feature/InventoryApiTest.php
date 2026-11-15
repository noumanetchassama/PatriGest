<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetInventory;
use App\Models\AssetInventoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InventoryApiTest extends TestCase
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

    private function makeAsset(string $ref = 'PG-MOB-0001'): Asset
    {
        return Asset::create([
            'reference' => $ref,
            'type' => Asset::TYPE_MOBILIER,
            'designation' => 'Chaise',
            'status' => Asset::STATUS_ACTIF,
        ]);
    }

    private function makeInventory(array $assetIds = []): AssetInventory
    {
        $inventory = AssetInventory::create([
            'name' => 'Inventaire Q1',
            'status' => AssetInventory::STATUS_EN_COURS,
            'started_by' => 1,
        ]);
        foreach ($assetIds as $id) {
            $inventory->items()->create(['asset_id' => $id]);
        }

        return $inventory;
    }

    public function test_creates_inventory_with_items(): void
    {
        $this->fakeAuth(['view-assets', 'manage-inventory']);
        $a1 = $this->makeAsset('PG-MOB-0001');
        $a2 = $this->makeAsset('PG-MOB-0002');

        $response = $this->postJson(
            '/api/v1/inventories',
            ['name' => 'Inventaire annuel', 'asset_ids' => [$a1->id, $a2->id, $a1->id]],
            $this->headers()
        );

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Inventaire annuel')
            ->assertJsonPath('data.status', 'en_cours')
            ->assertJsonPath('data.items_count', 2); // doublon ignoré

        $this->assertSame(2, AssetInventoryItem::count());
    }

    public function test_add_items_ignores_duplicates(): void
    {
        $this->fakeAuth(['view-assets', 'manage-inventory']);
        $asset = $this->makeAsset();
        $inventory = $this->makeInventory([$asset->id]);

        $this->postJson(
            "/api/v1/inventories/{$inventory->id}/items",
            ['asset_ids' => [$asset->id]],
            $this->headers()
        )->assertOk()->assertJsonPath('added', 0);

        $this->assertSame(1, $inventory->items()->count());
    }

    public function test_cannot_add_items_to_closed_inventory(): void
    {
        $this->fakeAuth(['view-assets', 'manage-inventory']);
        $asset = $this->makeAsset();
        $inventory = $this->makeInventory();
        $inventory->update(['status' => 'clos']);

        $this->postJson(
            "/api/v1/inventories/{$inventory->id}/items",
            ['asset_ids' => [$asset->id]],
            $this->headers()
        )->assertStatus(422);
    }

    public function test_check_item_marks_found(): void
    {
        $this->fakeAuth(['view-assets', 'manage-inventory']);
        $asset = $this->makeAsset();
        $inventory = $this->makeInventory([$asset->id]);
        $item = $inventory->items()->first();

        $this->postJson(
            "/api/v1/inventory-items/{$item->id}/check",
            ['status' => 'trouve', 'notes' => 'OK'],
            $this->headers()
        )->assertOk()
            ->assertJsonPath('data.status', 'trouve')
            ->assertJsonPath('data.asset_reference', 'PG-MOB-0001');

        $this->assertNotNull($item->fresh()->checked_at);
        $this->assertSame(1, $item->fresh()->checked_by);
    }

    public function test_cannot_check_twice(): void
    {
        $this->fakeAuth(['view-assets', 'manage-inventory']);
        $asset = $this->makeAsset();
        $inventory = $this->makeInventory([$asset->id]);
        $item = $inventory->items()->first();

        $this->postJson("/api/v1/inventory-items/{$item->id}/check", ['status' => 'trouve'], $this->headers());
        $this->postJson("/api/v1/inventory-items/{$item->id}/check", ['status' => 'absent'], $this->headers())
            ->assertStatus(422);

        $this->assertSame('trouve', $item->fresh()->status);
    }

    public function test_close_requires_all_items_checked(): void
    {
        $this->fakeAuth(['view-assets', 'manage-inventory']);
        $a1 = $this->makeAsset('PG-MOB-0001');
        $a2 = $this->makeAsset('PG-MOB-0002');
        $inventory = $this->makeInventory([$a1->id, $a2->id]);

        // Un seul bien vérifié → clôture refusée
        $this->postJson(
            "/api/v1/inventory-items/{$inventory->items()->first()->id}/check",
            ['status' => 'trouve'],
            $this->headers()
        );

        $this->postJson("/api/v1/inventories/{$inventory->id}/close", [], $this->headers())
            ->assertStatus(422);

        // Tous vérifiés → clôture OK
        $this->postJson(
            "/api/v1/inventory-items/{$inventory->items()->latest('id')->first()->id}/check",
            ['status' => 'absent', 'notes' => 'Introuvable'],
            $this->headers()
        );

        $this->postJson("/api/v1/inventories/{$inventory->id}/close", [], $this->headers())
            ->assertOk()->assertJsonPath('data.status', 'clos');

        $this->assertNotNull($inventory->fresh()->closed_at);
    }

    public function test_items_filter_by_status(): void
    {
        $this->fakeAuth(['view-assets']);
        $a1 = $this->makeAsset('PG-MOB-0001');
        $a2 = $this->makeAsset('PG-MOB-0002');
        $inventory = $this->makeInventory([$a1->id, $a2->id]);
        $inventory->items()->where('asset_id', $a1->id)->update(['status' => 'trouve']);

        $this->getJson("/api/v1/inventories/{$inventory->id}/items?status=trouve", $this->headers())
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_viewer_cannot_manage_inventory(): void
    {
        $this->fakeAuth(['view-assets']);

        $this->postJson('/api/v1/inventories', ['name' => 'X'], $this->headers())
            ->assertForbidden();
    }

    public function test_guest_is_rejected(): void
    {
        $this->getJson('/api/v1/inventories')->assertUnauthorized();
    }
}
