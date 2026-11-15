<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateViaAuthService;
use App\Http\Requests\StoreAssetDocumentRequest;
use App\Http\Resources\AssetDocumentResource;
use App\Models\Asset;
use App\Models\AssetDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetDocumentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $documents = AssetDocument::query()
            ->with('asset:id,reference,designation,type')
            ->when($request->filled('asset_id'), fn ($q) => $q->where('asset_id', $request->integer('asset_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return AssetDocumentResource::collection($documents);
    }

    public function indexForAsset(Request $request, Asset $asset): AnonymousResourceCollection
    {
        $request->merge(['asset_id' => $asset->id]);

        return $this->index($request);
    }

    public function store(StoreAssetDocumentRequest $request, Asset $asset): JsonResponse
    {
        $file = $request->file('file');

        $path = $file->store('documents', 'local');

        $document = AssetDocument::create([
            'asset_id' => $asset->id,
            'type' => $request->validated('type') ?? AssetDocument::TYPE_AUTRE,
            'name' => $request->validated('name') ?? $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'description' => $request->validated('description'),
            'uploaded_by' => AuthenticateViaAuthService::userId($request),
        ]);

        return (new AssetDocumentResource(
            $document->load('asset:id,reference,designation,type')
        ))->response()->setStatusCode(201);
    }

    public function show(AssetDocument $document): AssetDocumentResource
    {
        return new AssetDocumentResource(
            $document->load('asset:id,reference,designation,type')
        );
    }

    public function download(AssetDocument $document): StreamedResponse|JsonResponse
    {
        if (! Storage::disk('local')->exists($document->file_path)) {
            return response()->json(['message' => 'Fichier introuvable.'], 404);
        }

        return Storage::disk('local')->download(
            $document->file_path,
            $document->name
        );
    }

    public function destroy(AssetDocument $document): JsonResponse
    {
        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return response()->json(null, 204);
    }
}
