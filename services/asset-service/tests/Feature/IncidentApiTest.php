<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetIncident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IncidentApiTest extends TestCase
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

    private function makeAsset(): Asset
    {
        static $i = 0;
        $i++;

        return Asset::create([
            'reference' => "PG-VEH-{$i}",
            'type' => Asset::TYPE_VEHICULE,
            'designation' => 'Toyota Corolla',
            'status' => Asset::STATUS_ACTIF,
        ]);
    }

    public function test_creates_incident_ouvert(): void
    {
        $this->fakeAuth(['view-assets', 'manage-incidents']);
        $asset = $this->makeAsset();

        $response = $this->postJson(
            "/api/v1/assets/{$asset->id}/incidents",
            ['type' => 'accident', 'description' => 'Choc léger en stationnement', 'severity' => 'majeure'],
            $this->headers()
        );

        $response->assertCreated()
            ->assertJsonPath('data.type', 'accident')
            ->assertJsonPath('data.status', 'ouvert')
            ->assertJsonPath('data.severity', 'majeure')
            ->assertJsonPath('data.reported_by', 1);
    }

    public function test_index_filters_by_asset_status_severity(): void
    {
        $this->fakeAuth(['view-assets']);
        $asset = $this->makeAsset();
        AssetIncident::create([
            'asset_id' => $asset->id, 'type' => 'dommage', 'description' => 'Rayure',
            'occurred_at' => now(), 'severity' => 'mineure', 'status' => 'ouvert',
        ]);
        AssetIncident::create([
            'asset_id' => $this->makeAsset()->id, 'type' => 'vol', 'description' => 'Vol',
            'occurred_at' => now(), 'severity' => 'critique', 'status' => 'resolu',
        ]);

        $this->getJson(
            "/api/v1/incidents?asset_id={$asset->id}&status=ouvert",
            $this->headers()
        )->assertOk()->assertJsonCount(1, 'data');

        $this->getJson('/api/v1/incidents?severity=critique', $this->headers())
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_viewer_cannot_create_incident(): void
    {
        $this->fakeAuth(['view-assets']);
        $asset = $this->makeAsset();

        $this->postJson(
            "/api/v1/assets/{$asset->id}/incidents",
            ['type' => 'dommage', 'description' => 'Test'],
            $this->headers()
        )->assertForbidden();
    }

    public function test_resolve_then_close_lifecycle(): void
    {
        $this->fakeAuth(['view-assets', 'manage-incidents']);
        $asset = $this->makeAsset();
        $incident = AssetIncident::create([
            'asset_id' => $asset->id, 'type' => 'dommage', 'description' => 'Panne',
            'occurred_at' => now(), 'severity' => 'majeure', 'status' => 'ouvert',
        ]);

        // resolve
        $this->postJson(
            "/api/v1/incidents/{$incident->id}/resolve",
            ['resolution_notes' => 'Remplacé'],
            $this->headers()
        )->assertOk()
            ->assertJsonPath('data.status', 'resolu')
            ->assertJsonPath('data.resolution_notes', 'Remplacé');

        $this->assertNotNull($incident->fresh()->resolved_at);

        // close
        $this->postJson(
            "/api/v1/incidents/{$incident->id}/close",
            [],
            $this->headers()
        )->assertOk()->assertJsonPath('data.status', 'clos');
    }

    public function test_cannot_close_unresolved_incident(): void
    {
        $this->fakeAuth(['view-assets', 'manage-incidents']);
        $asset = $this->makeAsset();
        $incident = AssetIncident::create([
            'asset_id' => $asset->id, 'type' => 'dommage', 'description' => 'Panne',
            'occurred_at' => now(), 'status' => 'ouvert',
        ]);

        $this->postJson(
            "/api/v1/incidents/{$incident->id}/close",
            [],
            $this->headers()
        )->assertStatus(422);
    }

    public function test_cannot_resolve_twice(): void
    {
        $this->fakeAuth(['view-assets', 'manage-incidents']);
        $asset = $this->makeAsset();
        $incident = AssetIncident::create([
            'asset_id' => $asset->id, 'type' => 'vol', 'description' => 'Vol',
            'occurred_at' => now(), 'status' => 'ouvert',
        ]);

        $this->postJson("/api/v1/incidents/{$incident->id}/resolve", [], $this->headers());
        $this->postJson("/api/v1/incidents/{$incident->id}/resolve", [], $this->headers())
            ->assertStatus(422);
    }

    public function test_guest_is_rejected(): void
    {
        $this->getJson('/api/v1/incidents')->assertUnauthorized();
    }
}
