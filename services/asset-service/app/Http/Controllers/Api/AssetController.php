<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateViaAuthService;
use App\Http\Requests\StoreAssetLocationRequest;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Http\Resources\AssetLocationResource;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Models\AssetLocation;
use App\Models\MovableDetail;
use App\Models\RealEstateDetail;
use App\Models\VehicleDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AssetController extends Controller
{
    private const DETAIL_RELATIONS = [
        Asset::TYPE_IMMOBILIER => 'realEstateDetail',
        Asset::TYPE_MOBILIER => 'movableDetail',
        Asset::TYPE_VEHICULE => 'vehicleDetail',
    ];

    private const DETAIL_MODELS = [
        Asset::TYPE_IMMOBILIER => RealEstateDetail::class,
        Asset::TYPE_MOBILIER => MovableDetail::class,
        Asset::TYPE_VEHICULE => VehicleDetail::class,
    ];

    private const DETAIL_PAYLOAD_KEYS = [
        Asset::TYPE_IMMOBILIER => 'real_estate',
        Asset::TYPE_MOBILIER => 'movable',
        Asset::TYPE_VEHICULE => 'vehicle',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $assets = Asset::query()
            ->with('currentLocation')
            ->withCount('locations')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('etat'), fn ($q) => $q->where('etat', $request->string('etat')))
            ->when($request->filled('direction_id'), fn ($q) => $q->where('direction_id', $request->integer('direction_id')))
            ->when($request->filled('service_id'), fn ($q) => $q->where('service_id', $request->integer('service_id')))
            ->when($request->filled('custodian_user_id'), fn ($q) => $q->where('custodian_user_id', $request->integer('custodian_user_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = mb_strtolower($request->string('search')->toString());
                $q->where(function ($sub) use ($search) {
                    $sub->whereRaw('LOWER(designation) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(reference) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
                });
            })
            ->when($request->filled('value_min'), fn ($q) => $q->where('value', '>=', $request->float('value_min')))
            ->when($request->filled('value_max'), fn ($q) => $q->where('value', '<=', $request->float('value_max')))
            ->orderBy($request->input('sort', 'id'), $request->input('order', 'desc') === 'asc' ? 'asc' : 'desc')
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return AssetResource::collection($assets);
    }

    public function stats(): JsonResponse
    {
        $byType = Asset::query()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->map(fn ($total) => (int) $total);

        $byStatus = Asset::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total) => (int) $total);

        return response()->json([
            'data' => [
                'total' => Asset::query()->count(),
                'total_value' => (float) Asset::query()->sum('value'),
                'by_type' => $byType,
                'by_status' => $byStatus,
            ],
        ]);
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['real_estate', 'movable', 'vehicle', 'location']);
        $userId = AuthenticateViaAuthService::userId($request);

        $asset = DB::transaction(function () use ($data, $request, $userId) {
            $data['reference'] = Asset::generateReference($data['type']);
            $data['status'] = $data['status'] ?? Asset::STATUS_BROUILLON;

            $asset = Asset::create($data);

            $payloadKey = self::DETAIL_PAYLOAD_KEYS[$asset->type];
            $detailClass = self::DETAIL_MODELS[$asset->type];
            $detailData = $request->validated($payloadKey, []);

            if (! empty($detailData)) {
                $detailData['asset_id'] = $asset->id;
                $detailClass::create($detailData);
            }

            $location = $request->validated('location');
            if (! empty($location)) {
                AssetLocation::create($location + [
                    'asset_id' => $asset->id,
                    'is_current' => true,
                    'effective_from' => now(),
                    'created_by' => $userId,
                ]);
            }

            return $asset;
        });

        return (new AssetResource(
            $asset->fresh()->load([self::DETAIL_RELATIONS[$asset->type], 'currentLocation'])
        ))->response()->setStatusCode(201);
    }

    public function show(Request $request, Asset $asset): AssetResource
    {
        return new AssetResource(
            $asset->load([...array_values(self::DETAIL_RELATIONS), 'currentLocation'])
        );
    }

    public function update(UpdateAssetRequest $request, Asset $asset): AssetResource
    {
        $data = $request->safe()->except(['real_estate', 'movable', 'vehicle']);
        $asset->update($data);

        // Mise à jour du détail correspondant au type du bien
        $payloadKey = self::DETAIL_PAYLOAD_KEYS[$asset->type] ?? null;
        if ($payloadKey && $request->has($payloadKey)) {
            $detailClass = self::DETAIL_MODELS[$asset->type];
            $detailData = $request->validated($payloadKey, []);
            $relation = self::DETAIL_RELATIONS[$asset->type];

            $asset->{$relation}()->updateOrCreate(
                ['asset_id' => $asset->id],
                $detailData
            );
        }

        return new AssetResource(
            $asset->fresh()->load([...array_values(self::DETAIL_RELATIONS), 'currentLocation'])
        );
    }

    public function destroy(Request $request, Asset $asset): JsonResponse
    {
        $asset->delete(); // soft delete — jamais de suppression physique

        return response()->json(['message' => 'Bien supprimé (archive).']);
    }

    // ---------- Localisations ----------

    public function locations(Asset $asset): AnonymousResourceCollection
    {
        return AssetLocationResource::collection(
            $asset->locations()->orderByDesc('is_current')->orderByDesc('created_at')->paginate(20)
        );
    }

    public function storeLocation(StoreAssetLocationRequest $request, Asset $asset): JsonResponse
    {
        $location = DB::transaction(function () use ($request, $asset) {
            // Clôturer la localisation courante
            $asset->locations()->where('is_current', true)->update([
                'is_current' => false,
                'effective_to' => now(),
            ]);

            return AssetLocation::create($request->validated() + [
                'asset_id' => $asset->id,
                'is_current' => true,
                'effective_from' => $request->validated('effective_from') ?? now(),
                'created_by' => AuthenticateViaAuthService::userId($request),
            ]);
        });

        return (new AssetLocationResource($location))->response()->setStatusCode(201);
    }

    // ---------- Catégories (dynamiques, par type) ----------

    public function categories(Request $request)
    {
        $categories = Asset::query()
            ->select('type', 'category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->distinct()
            ->orderBy('type')
            ->orderBy('category')
            ->get()
            ->groupBy('type')
            ->map(fn ($items) => $items->pluck('category')->values());

        return response()->json(['data' => $categories]);
    }
}
