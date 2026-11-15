<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TransferApiTest extends TestCase
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
            'reference' => 'PG-VEH-0001',
            'type' => 'vehicule',
            'designation' => 'Toyota Corolla',
            'status' => 'actif',
            'direction_id' => 10,
            'service_id' => 20,
            'custodian_user_id' => 5,
        ]);
    }

    private function makeTransfer(Asset $asset, int $requestedBy = 1): AssetTransfer
    {
        return AssetTransfer::create([
            'asset_id' => $asset->id,
            'to_service_id' => 30,
            'to_custodian_user_id' => 42,
            'requested_by' => $requestedBy,
            'status' => AssetTransfer::STATUS_EN_ATTENTE,
        ]);
    }

    public function test_guest_is_rejected(): void
    {
        Http::fake();
        $this->getJson('/api/v1/transfers')->assertUnauthorized();
    }

    public function test_create_requires_permission(): void
    {
        $this->fakeAuth(['view-assignments']);
        $this->withHeaders($this->headers())->postJson('/api/v1/transfers', [
            'asset_id' => 1, 'to_custodian_user_id' => 42,
        ])->assertForbidden();
    }

    public function test_create_transfer_requires_a_target(): void
    {
        $this->fakeAuth(['view-assignments', 'create-transfer']);
        $asset = $this->makeAsset();

        $this->withHeaders($this->headers())->postJson('/api/v1/transfers', [
            'asset_id' => $asset->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('to_custodian_user_id');
    }

    public function test_create_transfer_pending(): void
    {
        $this->fakeAuth(['view-assignments', 'create-transfer']);
        $asset = $this->makeAsset();

        $this->withHeaders($this->headers())->postJson('/api/v1/transfers', [
            'asset_id' => $asset->id,
            'to_service_id' => 30,
            'to_custodian_user_id' => 42,
            'motif' => 'Mutation vers le service budget',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'en_attente')
            ->assertJsonPath('data.requested_by', 1);

        // Aucune mutation appliquée avant approbation
        $fresh = $asset->fresh();
        $this->assertEquals(20, $fresh->service_id);
        $this->assertEquals(5, $fresh->custodian_user_id);
    }

    public function test_cannot_approve_own_transfer(): void
    {
        $this->fakeAuth(['approve-transfer'], 1);
        $asset = $this->makeAsset();
        $transfer = $this->makeTransfer($asset, requestedBy: 1);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/transfers/{$transfer->id}/approve")
            ->assertForbidden();

        $this->assertEquals('en_attente', $transfer->fresh()->status);
    }

    public function test_approve_applies_mutation(): void
    {
        $this->fakeAuth(['approve-transfer'], 99);
        $asset = $this->makeAsset();
        $transfer = $this->makeTransfer($asset, requestedBy: 1);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/transfers/{$transfer->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approuvee')
            ->assertJsonPath('data.reviewed_by', 99);

        $fresh = $asset->fresh();
        $this->assertEquals(30, $fresh->service_id);
        $this->assertEquals(42, $fresh->custodian_user_id);
        $this->assertEquals(10, $fresh->direction_id); // inchangé (non fourni)
    }

    public function test_reject_requires_motif(): void
    {
        $this->fakeAuth(['approve-transfer'], 99);
        $asset = $this->makeAsset();
        $transfer = $this->makeTransfer($asset, requestedBy: 1);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/transfers/{$transfer->id}/reject", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('motif');
    }

    public function test_reject_keeps_asset_unchanged(): void
    {
        $this->fakeAuth(['approve-transfer'], 99);
        $asset = $this->makeAsset();
        $transfer = $this->makeTransfer($asset, requestedBy: 1);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/transfers/{$transfer->id}/reject", [
                'motif' => 'Mutation non justifiée.',
            ])->assertOk()
            ->assertJsonPath('data.status', 'rejetee');

        $this->assertEquals(20, $asset->fresh()->service_id);
    }

    public function test_cannot_decide_twice(): void
    {
        $this->fakeAuth(['approve-transfer'], 99);
        $asset = $this->makeAsset();
        $transfer = $this->makeTransfer($asset, requestedBy: 1);
        $transfer->update(['status' => 'approuvee', 'reviewed_by' => 99, 'decided_at' => now()]);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/transfers/{$transfer->id}/reject", ['motif' => 'trop tard'])
            ->assertUnprocessable();
    }

    public function test_requester_can_cancel_pending_only(): void
    {
        $this->fakeAuth(['view-assignments'], 1);
        $asset = $this->makeAsset();
        $transfer = $this->makeTransfer($asset, requestedBy: 1);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/transfers/{$transfer->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'annulee');

        $this->assertDatabaseHas('asset_transfers', [
            'id' => $transfer->id,
            'status' => 'annulee',
        ]);
    }

    public function test_other_user_cannot_cancel(): void
    {
        $this->fakeAuth(['view-assignments'], 77);
        $asset = $this->makeAsset();
        $transfer = $this->makeTransfer($asset, requestedBy: 1);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/transfers/{$transfer->id}/cancel")
            ->assertForbidden();
    }

    public function test_duplicate_pending_transfer_is_rejected(): void
    {
        $this->fakeAuth(['view-assignments', 'create-transfer']);
        $asset = $this->makeAsset();
        $this->makeTransfer($asset);

        $this->withHeaders($this->headers())->postJson('/api/v1/transfers', [
            'asset_id' => $asset->id,
            'to_custodian_user_id' => 43,
        ])->assertUnprocessable();
    }
}
