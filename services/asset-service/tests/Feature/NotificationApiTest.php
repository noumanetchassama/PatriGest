<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetSubmission;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationApiTest extends TestCase
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

    public function test_guest_is_rejected(): void
    {
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
    }

    public function test_lists_only_own_notifications(): void
    {
        $this->fakeAuth(['view-assets'], 7);

        Notification::create(['user_id' => 7, 'type' => 't', 'title' => 'Pour moi']);
        Notification::create(['user_id' => 8, 'type' => 't', 'title' => 'Pour un autre']);

        $res = $this->withHeaders($this->headers())->getJson('/api/v1/notifications');

        $res->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertSame('Pour moi', $res->json('data.0.title'));
    }

    public function test_unread_count_and_mark_read(): void
    {
        $this->fakeAuth(['view-assets'], 7);

        $n = Notification::create(['user_id' => 7, 'type' => 't', 'title' => 'N1']);

        $this->withHeaders($this->headers())
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJson(['unread' => 1]);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/notifications/{$n->id}/read")
            ->assertOk();

        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_cannot_mark_another_users_notification(): void
    {
        $this->fakeAuth(['view-assets'], 7);

        $n = Notification::create(['user_id' => 8, 'type' => 't', 'title' => 'Pas pour moi']);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/notifications/{$n->id}/read")
            ->assertForbidden();
    }

    public function test_approval_creates_notification_for_requester(): void
    {
        $asset = Asset::create([
            'reference' => 'PG-MOB-0001',
            'type' => 'mobilier',
            'designation' => 'Chaise',
            'status' => 'en_validation',
        ]);

        $submission = AssetSubmission::create([
            'asset_id' => $asset->id,
            'requested_by' => 2,
            'status' => AssetSubmission::STATUS_EN_ATTENTE,
        ]);

        $this->fakeAuth(['approve-asset-creation'], 4);

        $this->withHeaders($this->headers())
            ->postJson("/api/v1/asset-submissions/{$submission->id}/approve")
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => 2,
            'type' => 'soumission_approuvee',
        ]);
    }
}
