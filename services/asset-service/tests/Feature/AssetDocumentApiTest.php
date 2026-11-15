<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssetDocumentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

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
            'reference' => "PG-MOB-{$i}",
            'type' => Asset::TYPE_MOBILIER,
            'designation' => 'Chaise',
            'status' => Asset::STATUS_ACTIF,
        ]);
    }

    public function test_uploads_document(): void
    {
        $this->fakeAuth(['view-documents', 'manage-documents']);
        $asset = $this->makeAsset();

        $response = $this->postJson(
            "/api/v1/assets/{$asset->id}/documents",
            [
                'file' => UploadedFile::fake()->create('facture.pdf', 100, 'application/pdf'),
                'type' => 'facture',
                'name' => 'Facture achat 2026',
            ],
            $this->headers()
        );

        $response->assertCreated()
            ->assertJsonPath('data.type', 'facture')
            ->assertJsonPath('data.name', 'Facture achat 2026')
            ->assertJsonPath('data.asset_reference', 'PG-MOB-1')
            ->assertJsonPath('data.uploaded_by', 1);

        Storage::disk('local')->assertExists($response->json('data.id')
            ? AssetDocument::find($response->json('data.id'))->file_path : '');
    }

    public function test_index_filters_by_asset_and_type(): void
    {
        $this->fakeAuth(['view-documents']);
        $asset = $this->makeAsset();
        $other = $this->makeAsset();

        foreach ([[$asset, 'facture'], [$asset, 'contrat'], [$other, 'facture']] as [$a, $t]) {
            $doc = new AssetDocument([
                'asset_id' => $a->id, 'type' => $t, 'name' => "Doc {$t}",
                'file_path' => 'documents/x.pdf', 'mime_type' => 'application/pdf',
            ]);
            $doc->save();
        }

        $this->getJson("/api/v1/documents?asset_id={$asset->id}&type=facture", $this->headers())
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_viewer_cannot_upload(): void
    {
        $this->fakeAuth(['view-documents']);
        $asset = $this->makeAsset();

        $this->postJson(
            "/api/v1/assets/{$asset->id}/documents",
            ['file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')],
            $this->headers()
        )->assertForbidden();
    }

    public function test_requires_file(): void
    {
        $this->fakeAuth(['view-documents', 'manage-documents']);
        $asset = $this->makeAsset();

        $this->postJson("/api/v1/assets/{$asset->id}/documents", [], $this->headers())
            ->assertStatus(422);
    }

    public function test_download_returns_file(): void
    {
        $this->fakeAuth(['view-documents']);
        $asset = $this->makeAsset();
        Storage::disk('local')->put('documents/test.pdf', 'PDF-CONTENT');

        $doc = AssetDocument::create([
            'asset_id' => $asset->id, 'type' => 'facture', 'name' => 'Facture.pdf',
            'file_path' => 'documents/test.pdf', 'mime_type' => 'application/pdf',
        ]);

        $response = $this->get("/api/v1/documents/{$doc->id}/download", $this->headers());

        $response->assertOk();
        $this->assertSame('PDF-CONTENT', $response->streamedContent());
    }

    public function test_download_missing_file_returns_404(): void
    {
        $this->fakeAuth(['view-documents']);
        $asset = $this->makeAsset();

        $doc = AssetDocument::create([
            'asset_id' => $asset->id, 'type' => 'autre', 'name' => 'X',
            'file_path' => 'documents/absent.pdf',
        ]);

        $this->getJson("/api/v1/documents/{$doc->id}/download", $this->headers())
            ->assertStatus(404);
    }

    public function test_destroy_deletes_record_and_file(): void
    {
        $this->fakeAuth(['view-documents', 'manage-documents']);
        $asset = $this->makeAsset();
        Storage::disk('local')->put('documents/del.pdf', 'X');

        $doc = AssetDocument::create([
            'asset_id' => $asset->id, 'type' => 'autre', 'name' => 'Del',
            'file_path' => 'documents/del.pdf',
        ]);

        $this->deleteJson("/api/v1/documents/{$doc->id}", [], $this->headers())
            ->assertNoContent();

        $this->assertNull(AssetDocument::find($doc->id));
        Storage::disk('local')->assertMissing('documents/del.pdf');
    }

    public function test_viewer_cannot_delete(): void
    {
        $this->fakeAuth(['view-documents']);
        $doc = AssetDocument::create([
            'asset_id' => $this->makeAsset()->id, 'type' => 'autre', 'name' => 'X',
            'file_path' => 'documents/x.pdf',
        ]);

        $this->deleteJson("/api/v1/documents/{$doc->id}", [], $this->headers())
            ->assertForbidden();
    }

    public function test_guest_is_rejected(): void
    {
        $this->getJson('/api/v1/documents')->assertUnauthorized();
    }
}
