<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssetSubmissionApiTest extends TestCase
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
                    'roles' => ['agent-comptabilite'],
                    'permissions' => $permissions,
                ],
            ], 200),
        ]);
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer test-token'];
    }

    private function makeAsset(string $status = 'brouillon'): Asset
    {
        return Asset::create([
            'reference' => 'PG-IMM-0001',
            'type' => 'immobilier',
            'designation' => 'Bureau direction',
            'status' => $status,
        ]);
    }

    public function test_submit_requires_permission(): void
    {
        $this->fakeAuth(['view-assets']);
        $asset = $this->makeAsset();

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/assets/{$asset->id}/submit")
            ->assertForbidden();
    }

    public function test_submit_moves_asset_to_en_validation(): void
    {
        $this->fakeAuth(['view-assets', 'submit-assets']);
        $asset = $this->makeAsset();

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/assets/{$asset->id}/submit", ['motif' => 'Prêt pour validation'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'en_attente')
            ->assertJsonPath('data.requested_by', 1);

        $this->assertEquals('en_validation', $asset->fresh()->status);
    }

    public function test_cannot_submit_non_brouillon_asset(): void
    {
        $this->fakeAuth(['view-assets', 'submit-assets']);
        $asset = $this->makeAsset('actif');

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/assets/{$asset->id}/submit")
            ->assertUnprocessable();
    }

    public function test_cannot_submit_twice_while_pending(): void
    {
        $this->fakeAuth(['view-assets', 'submit-assets']);
        $asset = $this->makeAsset();
        $asset->update(['status' => 'en_validation']);
        AssetSubmission::create([
            'asset_id' => $asset->id,
            'requested_by' => 1,
            'status' => 'en_attente',
        ]);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/assets/{$asset->id}/submit")
            ->assertUnprocessable();
    }

    public function test_cannot_approve_own_submission(): void
    {
        $this->fakeAuth(['approve-asset-creation'], 1);
        $asset = $this->makeAsset('en_validation');
        $submission = AssetSubmission::create([
            'asset_id' => $asset->id,
            'requested_by' => 1,
            'status' => 'en_attente',
        ]);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/asset-submissions/{$submission->id}/approve")
            ->assertForbidden();

        $this->assertEquals('en_validation', $asset->fresh()->status);
    }

    public function test_approve_makes_asset_active(): void
    {
        $this->fakeAuth(['approve-asset-creation'], 99);
        $asset = $this->makeAsset('en_validation');
        $submission = AssetSubmission::create([
            'asset_id' => $asset->id,
            'requested_by' => 1,
            'status' => 'en_attente',
        ]);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/asset-submissions/{$submission->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approuvee')
            ->assertJsonPath('data.reviewed_by', 99);

        $this->assertEquals('actif', $asset->fresh()->status);
    }

    public function test_reject_requires_motif_and_returns_to_brouillon(): void
    {
        $this->fakeAuth(['approve-asset-creation'], 99);
        $asset = $this->makeAsset('en_validation');
        $submission = AssetSubmission::create([
            'asset_id' => $asset->id,
            'requested_by' => 1,
            'status' => 'en_attente',
        ]);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/asset-submissions/{$submission->id}/reject", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('motif');

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/asset-submissions/{$submission->id}/reject", [
                'motif' => 'Documents incomplets.',
            ])->assertOk()
            ->assertJsonPath('data.status', 'rejetee')
            ->assertJsonPath('data.decision_motif', 'Documents incomplets.');

        $this->assertEquals('brouillon', $asset->fresh()->status);
    }

    public function test_en_validation_cannot_be_set_via_api(): void
    {
        $this->fakeAuth(['view-assets', 'create-assets', 'edit-assets']);

        $this->withHeaders($this->headers())->postJson('/api/v1/assets', [
            'type' => 'mobilier',
            'designation' => 'Test statut',
            'status' => 'en_validation',
            'movable' => [],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_cannot_decide_submission_twice(): void
    {
        $this->fakeAuth(['approve-asset-creation'], 99);
        $asset = $this->makeAsset('en_validation');
        $submission = AssetSubmission::create([
            'asset_id' => $asset->id,
            'requested_by' => 1,
            'status' => 'approuvee',
            'reviewed_by' => 99,
            'decided_at' => now(),
        ]);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/asset-submissions/{$submission->id}/reject", ['motif' => 'trop tard'])
            ->assertUnprocessable();
    }

    public function test_index_filters_submissions(): void
    {
        $this->fakeAuth(['view-assets']);
        $asset = $this->makeAsset();
        AssetSubmission::create([
            'asset_id' => $asset->id, 'requested_by' => 1, 'status' => 'en_attente',
        ]);
        AssetSubmission::create([
            'asset_id' => $asset->id, 'requested_by' => 2, 'status' => 'rejetee',
        ]);

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/asset-submissions?status=en_attente')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
