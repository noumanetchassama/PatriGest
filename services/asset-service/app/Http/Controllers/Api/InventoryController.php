<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateViaAuthService;
use App\Http\Requests\CheckInventoryItemRequest;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Resources\AssetInventoryItemResource;
use App\Http\Resources\AssetInventoryResource;
use App\Models\AssetInventory;
use App\Models\AssetInventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $inventories = AssetInventory::query()
            ->withCount('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return AssetInventoryResource::collection($inventories);
    }

    public function store(StoreInventoryRequest $request): JsonResponse
    {
        $inventory = DB::transaction(function () use ($request) {
            $inventory = AssetInventory::create([
                'name' => $request->validated('name'),
                'description' => $request->validated('description'),
                'status' => AssetInventory::STATUS_EN_COURS,
                'started_by' => AuthenticateViaAuthService::userId($request),
            ]);

            $this->syncItems($inventory, $request->validated('asset_ids', []));

            return $inventory;
        });

        return (new AssetInventoryResource(
            $inventory->loadCount('items')
        ))->response()->setStatusCode(201);
    }

    public function show(AssetInventory $inventory): AssetInventoryResource
    {
        return new AssetInventoryResource(
            $inventory->loadCount('items')->load('items')
        );
    }

    public function items(Request $request, AssetInventory $inventory): AnonymousResourceCollection
    {
        $items = $inventory->items()
            ->with('asset:id,reference,designation,type')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByRaw("CASE status WHEN 'en_attente' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->paginate(min((int) $request->input('per_page', 50), 200));

        return AssetInventoryItemResource::collection($items);
    }

    /**
     * Ajoute des biens à une campagne en cours (ignorés s'ils y figurent déjà).
     */
    public function addItems(Request $request, AssetInventory $inventory): JsonResponse|AssetInventoryResource
    {
        if ($inventory->status !== AssetInventory::STATUS_EN_COURS) {
            return response()->json([
                'message' => 'Impossible d’ajouter des biens à un inventaire clos.',
            ], 422);
        }

        $request->validate([
            'asset_ids' => ['required', 'array', 'min:1'],
            'asset_ids.*' => ['integer', 'exists:assets,id'],
        ]);

        $added = $this->syncItems($inventory, $request->input('asset_ids'));

        return (new AssetInventoryResource(
            $inventory->fresh()->loadCount('items')
        ))->additional(['added' => $added]);
    }

    /**
     * Marque un bien comme vérifié (trouvé / absent / non concerné).
     */
    public function checkItem(
        CheckInventoryItemRequest $request,
        AssetInventoryItem $item
    ): AssetInventoryItemResource|JsonResponse {
        if ($item->inventory->status !== AssetInventory::STATUS_EN_COURS) {
            return response()->json([
                'message' => 'L’inventaire est clos.',
            ], 422);
        }

        if ($item->status !== AssetInventoryItem::STATUS_EN_ATTENTE) {
            return response()->json([
                'message' => 'Ce bien a déjà été vérifié.',
            ], 422);
        }

        $item->update([
            'status' => $request->validated('status'),
            'notes' => $request->validated('notes'),
            'checked_by' => AuthenticateViaAuthService::userId($request),
            'checked_at' => now(),
        ]);

        return new AssetInventoryItemResource(
            $item->fresh()->load('asset:id,reference,designation,type')
        );
    }

    /**
     * Clôture la campagne — exige que tous les biens soient vérifiés.
     */
    public function close(Request $request, AssetInventory $inventory): AssetInventoryResource|JsonResponse
    {
        if ($inventory->status !== AssetInventory::STATUS_EN_COURS) {
            return response()->json(['message' => 'Cet inventaire est déjà clos.'], 422);
        }

        $pending = $inventory->pendingItemsCount();
        if ($pending > 0) {
            return response()->json([
                'message' => "{$pending} bien(s) restent à vérifier avant la clôture.",
            ], 422);
        }

        $inventory->update([
            'status' => AssetInventory::STATUS_CLOS,
            'closed_by' => AuthenticateViaAuthService::userId($request),
            'closed_at' => now(),
        ]);

        return new AssetInventoryResource(
            $inventory->fresh()->loadCount('items')->load('items')
        );
    }

    /**
     * Insère les lignes manquantes (anti-doublon via contrainte unique).
     */
    private function syncItems(AssetInventory $inventory, array $assetIds): int
    {
        $existing = $inventory->items()->pluck('asset_id')->all();
        $toAdd = array_diff(array_unique($assetIds), $existing);

        foreach ($toAdd as $assetId) {
            $inventory->items()->create(['asset_id' => $assetId]);
        }

        return count($toAdd);
    }
}
