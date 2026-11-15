<?php

namespace Tests\Feature;

use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssetApiTest extends TestCase
{
    use RefreshDatabase;

    private function fakeAuth(array $permissions = ['view-assets', 'create-assets', 'edit-assets']): void
    {
        Http::fake([
            '*/api/v1/auth/me' => Http::response([
                'data' => [
                    'id' => 1,
                    'name' => 'Agent Test',
                    'email' => 'agent@test.local',
                    'roles' => ['agent-parc-auto'],
                    'permissions' => $permissions,
                ],
            ], 200),
        ]);
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer test-token'];
    }

    public function test_guest_is_rejected(): void
    {
        Http::fake();
        $this->getJson('/api/v1/assets')->assertUnauthorized();
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        $this->fakeAuth(['view-reports']);
        $this->withHeaders($this->headers())
            ->getJson('/api/v1/assets')
            ->assertForbidden();
    }

    public function test_stats_returns_totals_by_type_and_status(): void
    {
        $this->fakeAuth();

        Asset::create([
            'reference' => 'PG-MOB-0001',
            'type' => 'mobilier',
            'designation' => 'Ordinateur portable',
            'value' => 450000,
            'status' => 'actif',
        ]);
        Asset::create([
            'reference' => 'PG-MOB-0002',
            'type' => 'mobilier',
            'designation' => 'Imprimante',
            'value' => 125000,
            'status' => 'brouillon',
        ]);
        Asset::create([
            'reference' => 'PG-IMM-0001',
            'type' => 'immobilier',
            'designation' => 'Bureau',
            'status' => 'actif',
        ]);

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/assets/stats')
            ->assertOk()
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.total_value', 575000)
            ->assertJsonPath('data.by_type.mobilier', 2)
            ->assertJsonPath('data.by_type.immobilier', 1)
            ->assertJsonPath('data.by_status.actif', 2)
            ->assertJsonPath('data.by_status.brouillon', 1);
    }

    public function test_create_movable_asset_generates_reference_and_detail(): void
    {
        $this->fakeAuth();

        $response = $this->withHeaders($this->headers())->postJson('/api/v1/assets', [
            'type' => 'mobilier',
            'designation' => 'Ordinateur portable Dell',
            'category' => 'informatique',
            'value' => 450000,
            'movable' => [
                'marque' => 'Dell',
                'modele' => 'Latitude 5540',
                'numero_serie' => 'SN-12345',
            ],
            'location' => [
                'region' => 'Maritime',
                'prefecture' => 'Lomé',
                'commune' => 'Agoè',
                'latitude' => 6.1,
                'longitude' => 1.2,
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.reference', 'PG-MOB-0001')
            ->assertJsonPath('data.type', 'mobilier')
            ->assertJsonPath('data.details.marque', 'Dell')
            ->assertJsonPath('data.current_location.commune', 'Agoè');

        $this->assertDatabaseHas('assets', ['reference' => 'PG-MOB-0001']);
        $this->assertDatabaseHas('movable_details', ['numero_serie' => 'SN-12345']);
        $this->assertDatabaseHas('asset_locations', ['commune' => 'Agoè', 'is_current' => true]);
    }

    public function test_references_are_sequential_per_type(): void
    {
        $this->fakeAuth();

        foreach (['mobilier', 'vehicule', 'immobilier'] as $i => $type) {
            $payload = ['type' => $type, 'designation' => "Bien {$type} {$i}"];
            $payload[match ($type) {
                'mobilier' => 'movable',
                'vehicule' => 'vehicle',
                'immobilier' => 'real_estate',
            }] = [];

            $this->withHeaders($this->headers())->postJson('/api/v1/assets', $payload);
        }

        $this->assertDatabaseHas('assets', ['reference' => 'PG-MOB-0001']);
        $this->assertDatabaseHas('assets', ['reference' => 'PG-VEH-0001']);
        $this->assertDatabaseHas('assets', ['reference' => 'PG-IMM-0001']);
    }

    public function test_vehicle_requires_vehicle_details_payload(): void
    {
        $this->fakeAuth();

        $this->withHeaders($this->headers())->postJson('/api/v1/assets', [
            'type' => 'vehicule',
            'designation' => 'Voiture sans détails',
        ])->assertUnprocessable();
    }

    public function test_immobilier_details_stored_in_own_table(): void
    {
        $this->fakeAuth();

        $this->withHeaders($this->headers())->postJson('/api/v1/assets', [
            'type' => 'immobilier',
            'designation' => 'Bureau direction',
            'real_estate' => [
                'bien_type' => 'bureau',
                'surface_batie' => 120.5,
                'nb_pieces' => 4,
                'annee_construction' => 2010,
            ],
        ])->assertCreated()
            ->assertJsonPath('data.reference', 'PG-IMM-0001')
            ->assertJsonPath('data.details.surface_batie', '120.50');

        $this->assertDatabaseHas('real_estate_details', ['nb_pieces' => 4]);
        $this->assertDatabaseMissing('movable_details', ['asset_id' => 1]);
    }

    public function test_index_filters_and_pagination(): void
    {
        $this->fakeAuth();

        $this->withHeaders($this->headers())->postJson('/api/v1/assets', [
            'type' => 'mobilier', 'designation' => 'Imprimante', 'movable' => [],
        ]);
        $this->withHeaders($this->headers())->postJson('/api/v1/assets', [
            'type' => 'vehicule', 'designation' => 'Camionnette', 'vehicle' => [],
        ]);

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/assets?type=mobilier')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/assets?search=camionnette')
            ->assertOk()
            ->assertJsonPath('data.0.reference', 'PG-VEH-0001');
    }

    public function test_show_returns_details_and_locations(): void
    {
        $this->fakeAuth();

        $create = $this->withHeaders($this->headers())->postJson('/api/v1/assets', [
            'type' => 'vehicule',
            'designation' => 'Pick-up',
            'vehicle' => ['immatriculation' => 'TGO-1234'],
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->withHeaders($this->headers())
            ->getJson("/api/v1/assets/{$id}")
            ->assertOk()
            ->assertJsonPath('data.details.immatriculation', 'TGO-1234');
    }

    public function test_update_asset_and_detail(): void
    {
        $this->fakeAuth();

        $create = $this->withHeaders($this->headers())->postJson('/api/v1/assets', [
            'type' => 'mobilier',
            'designation' => 'PC fixe',
            'movable' => ['marque' => 'HP'],
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->withHeaders($this->headers())
            ->putJson("/api/v1/assets/{$id}", [
                'designation' => 'PC fixe Dell',
                'movable' => ['marque' => 'Dell', 'modele' => 'OptiPlex'],
            ])
            ->assertOk()
            ->assertJsonPath('data.designation', 'PC fixe Dell')
            ->assertJsonPath('data.details.marque', 'Dell');
    }

    public function test_location_history_is_preserved(): void
    {
        $this->fakeAuth();

        $create = $this->withHeaders($this->headers())->postJson('/api/v1/assets', [
            'type' => 'mobilier',
            'designation' => 'Table',
            'movable' => [],
            'location' => ['commune' => 'Lomé'],
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/assets/{$id}/locations", [
                'commune' => 'Kara',
                'region' => 'Kara',
            ])
            ->assertCreated();

        // L'ancienne localisation est clôturée, la nouvelle est courante
        $this->assertSame(1, Asset::find($id)->locations()->where('is_current', true)->count());
        $this->assertSame(1, Asset::find($id)->locations()->where('is_current', false)->count());

        $this->withHeaders($this->headers())
            ->getJson("/api/v1/assets/{$id}/locations")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_soft_delete_keeps_record(): void
    {
        $this->fakeAuth();

        $create = $this->withHeaders($this->headers())->postJson('/api/v1/assets', [
            'type' => 'mobilier',
            'designation' => 'Chaise',
            'movable' => [],
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->withHeaders($this->headers())
            ->deleteJson("/api/v1/assets/{$id}")
            ->assertOk();

        $this->assertSoftDeleted('assets', ['id' => $id]);
        $this->assertDatabaseHas('assets', ['id' => $id, 'reference' => 'PG-MOB-0001']);
    }

    public function test_cannot_create_without_create_permission(): void
    {
        $this->fakeAuth(['view-assets']);

        $this->withHeaders($this->headers())->postJson('/api/v1/assets', [
            'type' => 'mobilier',
            'designation' => 'X',
            'movable' => [],
        ])->assertForbidden();
    }
}
