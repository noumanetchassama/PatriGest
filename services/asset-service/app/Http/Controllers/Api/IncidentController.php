<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateViaAuthService;
use App\Http\Requests\ResolveIncidentRequest;
use App\Http\Requests\StoreIncidentRequest;
use App\Http\Resources\AssetIncidentResource;
use App\Models\Asset;
use App\Models\AssetIncident;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IncidentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $incidents = AssetIncident::query()
            ->with('asset:id,reference,designation,type')
            ->when($request->filled('asset_id'), fn ($q) => $q->where('asset_id', $request->integer('asset_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->string('severity')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderByRaw("CASE status WHEN 'ouvert' THEN 0 WHEN 'en_cours' THEN 1 ELSE 2 END")
            ->orderByDesc('occurred_at')
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return AssetIncidentResource::collection($incidents);
    }

    public function store(StoreIncidentRequest $request, Asset $asset): JsonResponse
    {
        $incident = AssetIncident::create($request->validated() + [
            'asset_id' => $asset->id,
            'occurred_at' => $request->validated('occurred_at') ?? now(),
            'severity' => $request->validated('severity') ?? AssetIncident::SEVERITY_MINEURE,
            'status' => AssetIncident::STATUS_OUVERT,
            'reported_by' => AuthenticateViaAuthService::userId($request),
        ]);

        return (new AssetIncidentResource(
            $incident->load('asset:id,reference,designation,type')
        ))->response()->setStatusCode(201);
    }

    public function show(AssetIncident $incident): AssetIncidentResource
    {
        return new AssetIncidentResource(
            $incident->load('asset:id,reference,designation,type')
        );
    }

    /**
     * Marque l'incident comme résolu (notes de résolution conservées).
     */
    public function resolve(ResolveIncidentRequest $request, AssetIncident $incident): AssetIncidentResource|JsonResponse
    {
        if (in_array($incident->status, [
            AssetIncident::STATUS_RESOLU,
            AssetIncident::STATUS_CLOS,
        ], true)) {
            return response()->json([
                'message' => 'Cet incident est déjà résolu ou clos.',
            ], 422);
        }

        $incident->update([
            'status' => AssetIncident::STATUS_RESOLU,
            'resolution_notes' => $request->validated('resolution_notes'),
            'resolved_at' => now(),
        ]);

        return new AssetIncidentResource(
            $incident->fresh()->load('asset:id,reference,designation,type')
        );
    }

    /**
     * Clôture définitivement l'incident (uniquement s'il est résolu).
     */
    public function close(Request $request, AssetIncident $incident): AssetIncidentResource|JsonResponse
    {
        if ($incident->status !== AssetIncident::STATUS_RESOLU) {
            return response()->json([
                'message' => 'Seul un incident résolu peut être clos.',
            ], 422);
        }

        $incident->update(['status' => AssetIncident::STATUS_CLOS]);

        return new AssetIncidentResource(
            $incident->fresh()->load('asset:id,reference,designation,type')
        );
    }
}
