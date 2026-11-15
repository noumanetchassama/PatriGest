<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssignmentApiTest extends TestCase
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
        return Asset::create([
            'reference' => 'PG-MOB-9999',
            'type' => 'mobilier',
            'designation' => 'Chaise de bureau',
            'status' => 'actif',
        ]);
    }

    private function makeAssignment(Asset $asset, int $requestedBy = 1): AssetAssignment
    {
        return AssetAssignment::create([
            'asset_id' => $asset->id,
            'custodian_user_id' => 42,
            'requested_by' => $requestedBy,
            'status' => AssetAssignment::STATUS_EN_ATTENTE,
        ]);
    }

    public function test_guest_is_rejected(): void
    {
        Http::fake();
        $this->getJson('/api/v1/assignments')->assertUnauthorized();
    }

    public function test_create_requires_permission(): void
    {
        $this->fakeAuth(['view-assignments']);
        $this->withHeaders($this->headers())->postJson('/api/v1/assignments', [
            'asset_id' => 1, 'custodian_user_id' => 42,
        ])->assertForbidden();
    }

    public function test_create_assignment_pending(): void
    {
        $this->fakeAuth(['view-assignments', 'create-assignments']);
        $asset = $this->makeAsset();

        $this->withHeaders($this->headers())->postJson('/api/v1/assignments', [
            'asset_id' => $asset->id,
            'custodian_user_id' => 42,
            'motif' => 'Affectation au service comptabilité',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'en_attente')
            ->assertJsonPath('data.requested_by', 1)
            ->assertJsonPath('data.custodian_user_id', 42);

        // Le dépositaire n'est PAS encore modifié
        $this->assertNull($asset->fresh()->custodian_user_id);
    }

    public function test_duplicate_pending_assignment_is_rejected(): void
    {
        $this->fakeAuth(['view-assignments', 'create-assignments']);
        $asset = $this->makeAsset();
        $this->makeAssignment($asset);

        $this->withHeaders($this->headers())->postJson('/api/v1/assignments', [
            'asset_id' => $asset->id,
            'custodian_user_id' => 42,
        ])->assertUnprocessable();
    }

    public function test_cannot_approve_own_assignment(): void
    {
        $this->fakeAuth(['approve-assignment'], 1);
        $asset = $this->makeAsset();
        $assignment = $this->makeAssignment($asset, requestedBy: 1);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/assignments/{$assignment->id}/approve")
            ->assertForbidden()
            ->assertJsonPath('message', 'Vous ne pouvez pas approuver votre propre demande.');

        $this->assertEquals('en_attente', $assignment->fresh()->status);
        $this->assertNull($asset->fresh()->custodian_user_id);
    }

    public function test_approve_by_another_user_updates_custodian(): void
    {
        $this->fakeAuth(['approve-assignment'], 99);
        $asset = $this->makeAsset();
        $assignment = $this->makeAssignment($asset, requestedBy: 1);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/assignments/{$assignment->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approuvee')
            ->assertJsonPath('data.reviewed_by', 99);

        $this->assertEquals(42, $asset->fresh()->custodian_user_id);
    }

    public function test_reject_requires_motif(): void
    {
        $this->fakeAuth(['approve-assignment'], 99);
        $asset = $this->makeAsset();
        $assignment = $this->makeAssignment($asset, requestedBy: 1);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/assignments/{$assignment->id}/reject", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('motif');
    }

    public function test_reject_with_motif_keeps_history(): void
    {
        $this->fakeAuth(['approve-assignment'], 99);
        $asset = $this->makeAsset();
        $assignment = $this->makeAssignment($asset, requestedBy: 1);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/assignments/{$assignment->id}/reject", [
                'motif' => 'Bien déjà affecté à un autre agent.',
            ])->assertOk()
            ->assertJsonPath('data.status', 'rejetee')
            ->assertJsonPath('data.decision_motif', 'Bien déjà affecté à un autre agent.');

        // Le dépositaire reste inchangé
        $this->assertNull($asset->fresh()->custodian_user_id);
    }

    public function test_new_assignment_possible_after_reject(): void
    {
        $this->fakeAuth(['view-assignments', 'create-assignments'], 1);
        $asset = $this->makeAsset();

        // Demande rejetée existante (historique conservé)
        AssetAssignment::create([
            'asset_id' => $asset->id,
            'custodian_user_id' => 42,
            'requested_by' => 2,
            'status' => 'rejetee',
            'reviewed_by' => 99,
            'decision_motif' => 'Refusé',
            'decided_at' => now(),
        ]);

        // Une nouvelle demande est acceptée
        $this->withHeaders($this->headers())->postJson('/api/v1/assignments', [
            'asset_id' => $asset->id,
            'custodian_user_id' => 43,
        ])->assertCreated();
    }

    public function test_cannot_decide_twice(): void
    {
        $this->fakeAuth(['approve-assignment'], 99);
        $asset = $this->makeAsset();
        $assignment = $this->makeAssignment($asset, requestedBy: 1);
        $assignment->update(['status' => 'approuvee', 'reviewed_by' => 99, 'decided_at' => now()]);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/assignments/{$assignment->id}/reject", ['motif' => 'trop tard'])
            ->assertUnprocessable();
    }

    public function test_requester_can_cancel_pending_only(): void
    {
        $this->fakeAuth(['view-assignments'], 1);
        $asset = $this->makeAsset();
        $assignment = $this->makeAssignment($asset, requestedBy: 1);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/assignments/{$assignment->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'annulee');

        // L'historique est conservé
        $this->assertDatabaseHas('asset_assignments', [
            'id' => $assignment->id,
            'status' => 'annulee',
        ]);
    }

    public function test_other_user_cannot_cancel(): void
    {
        $this->fakeAuth(['view-assignments'], 77);
        $asset = $this->makeAsset();
        $assignment = $this->makeAssignment($asset, requestedBy: 1);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/assignments/{$assignment->id}/cancel")
            ->assertForbidden();
    }

    public function test_index_filters_by_status(): void
    {
        $this->fakeAuth(['view-assignments']);
        $asset = $this->makeAsset();
        $this->makeAssignment($asset);
        AssetAssignment::create([
            'asset_id' => $asset->id,
            'custodian_user_id' => 42,
            'requested_by' => 1,
            'status' => 'rejetee',
        ]);

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/assignments?status=en_attente')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
