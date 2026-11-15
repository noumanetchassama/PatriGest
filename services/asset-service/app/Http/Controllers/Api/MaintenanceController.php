<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateViaAuthService;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Http\Requests\UpdateMaintenanceRequest;
use App\Http\Resources\AssetMaintenanceResource;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $maintenances = AssetMaintenance::query()
            ->with('asset:id,reference,designation,type')
            ->when($request->filled('asset_id'), fn ($q) => $q->where('asset_id', $request->integer('asset_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->orderByRaw("CASE status WHEN 'en_cours' THEN 0 WHEN 'planifiee' THEN 1 ELSE 2 END")
            ->orderByDesc('scheduled_date')
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return AssetMaintenanceResource::collection($maintenances);
    }

    public function indexForAsset(Request $request, Asset $asset): AnonymousResourceCollection
    {
        $request->merge(['asset_id' => $asset->id]);

        return $this->index($request);
    }

    public function store(StoreMaintenanceRequest $request, Asset $asset): JsonResponse
    {
        $maintenance = AssetMaintenance::create($request->validated() + [
            'asset_id' => $asset->id,
            'status' => AssetMaintenance::STATUS_PLANIFIEE,
            'created_by' => AuthenticateViaAuthService::userId($request),
        ]);

        return (new AssetMaintenanceResource(
            $maintenance->load('asset:id,reference,designation,type')
        ))->response()->setStatusCode(201);
    }

    public function show(AssetMaintenance $maintenance): AssetMaintenanceResource
    {
        return new AssetMaintenanceResource(
            $maintenance->load('asset:id,reference,designation,type')
        );
    }

    public function update(UpdateMaintenanceRequest $request, AssetMaintenance $maintenance): AssetMaintenanceResource|JsonResponse
    {
        // Une maintenance commencée ou terminée n'est plus modifiable librement
        if (in_array($maintenance->status, [
            AssetMaintenance::STATUS_EN_COURS,
            AssetMaintenance::STATUS_TERMINEE,
            AssetMaintenance::STATUS_ANNULEE,
        ], true)) {
            return response()->json([
                'message' => 'Cette maintenance ne peut plus être modifiée.',
            ], 422);
        }

        $maintenance->update($request->validated());

        return new AssetMaintenanceResource(
            $maintenance->fresh()->load('asset:id,reference,designation,type')
        );
    }

    /**
     * Démarre la maintenance : le bien passe au statut `maintenance`.
     */
    public function start(Request $request, AssetMaintenance $maintenance): AssetMaintenanceResource|JsonResponse
    {
        if ($maintenance->status !== AssetMaintenance::STATUS_PLANIFIEE) {
            return response()->json([
                'message' => 'Seule une maintenance planifiée peut démarrer.',
            ], 422);
        }

        DB::transaction(function () use ($maintenance) {
            $asset = $maintenance->asset;

            $maintenance->update([
                'status' => AssetMaintenance::STATUS_EN_COURS,
                'previous_asset_status' => $asset->status,
            ]);

            $asset->update(['status' => Asset::STATUS_MAINTENANCE]);
        });

        return new AssetMaintenanceResource(
            $maintenance->fresh()->load('asset:id,reference,designation,type')
        );
    }

    /**
     * Termine la maintenance : le bien retrouve son statut antérieur.
     */
    public function complete(Request $request, AssetMaintenance $maintenance): AssetMaintenanceResource|JsonResponse
    {
        if ($maintenance->status !== AssetMaintenance::STATUS_EN_COURS) {
            return response()->json([
                'message' => 'Seule une maintenance en cours peut être terminée.',
            ], 422);
        }

        $data = $request->validate([
            'realized_date' => ['nullable', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($maintenance, $data) {
            $maintenance->update($data + [
                'status' => AssetMaintenance::STATUS_TERMINEE,
                'realized_date' => $data['realized_date'] ?? now()->toDateString(),
            ]);

            $maintenance->asset()->update([
                'status' => $maintenance->previous_asset_status ?? Asset::STATUS_ACTIF,
            ]);
        });

        return new AssetMaintenanceResource(
            $maintenance->fresh()->load('asset:id,reference,designation,type')
        );
    }

    public function cancel(Request $request, AssetMaintenance $maintenance): AssetMaintenanceResource|JsonResponse
    {
        if (! in_array($maintenance->status, [
            AssetMaintenance::STATUS_PLANIFIEE,
            AssetMaintenance::STATUS_EN_COURS,
        ], true)) {
            return response()->json([
                'message' => 'Cette maintenance ne peut plus être annulée.',
            ], 422);
        }

        DB::transaction(function () use ($maintenance) {
            $wasInProgress = $maintenance->status === AssetMaintenance::STATUS_EN_COURS;

            $maintenance->update(['status' => AssetMaintenance::STATUS_ANNULEE]);

            // Si la maintenance était en cours, restaurer le statut du bien
            if ($wasInProgress) {
                $maintenance->asset()->update([
                    'status' => $maintenance->previous_asset_status ?? Asset::STATUS_ACTIF,
                ]);
            }
        });

        return new AssetMaintenanceResource(
            $maintenance->fresh()->load('asset:id,reference,designation,type')
        );
    }
}
