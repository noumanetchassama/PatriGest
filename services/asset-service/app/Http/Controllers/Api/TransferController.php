<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateViaAuthService;
use App\Http\Requests\RejectTransferRequest;
use App\Http\Requests\StoreTransferRequest;
use App\Http\Resources\AssetTransferResource;
use App\Models\Asset;
use App\Support\Notifier;
use App\Models\AssetTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $transfers = AssetTransfer::query()
            ->with('asset:id,reference,designation,type')
            ->when($request->filled('asset_id'), fn ($q) => $q->where('asset_id', $request->integer('asset_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('requested_by'), fn ($q) => $q->where('requested_by', $request->integer('requested_by')))
            ->latest()
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return AssetTransferResource::collection($transfers);
    }

    public function store(StoreTransferRequest $request): JsonResponse
    {
        $userId = AuthenticateViaAuthService::userId($request);

        $transfer = DB::transaction(function () use ($request, $userId) {
            $asset = Asset::findOrFail($request->integer('asset_id'));

            // Une seule mutation en attente par bien à la fois
            if ($asset->transfers()->where('status', AssetTransfer::STATUS_EN_ATTENTE)->exists()) {
                throw ValidationException::withMessages([
                    'asset_id' => 'Un transfert est déjà en attente pour ce bien.',
                ]);
            }

            return AssetTransfer::create([
                'asset_id' => $asset->id,
                'to_direction_id' => $request->validated('to_direction_id'),
                'to_service_id' => $request->validated('to_service_id'),
                'to_custodian_user_id' => $request->validated('to_custodian_user_id'),
                'requested_by' => $userId,
                'status' => AssetTransfer::STATUS_EN_ATTENTE,
                'motif' => $request->validated('motif'),
            ]);
        });

        return (new AssetTransferResource(
            $transfer->load('asset:id,reference,designation,type')
        ))->response()->setStatusCode(201);
    }

    public function show(AssetTransfer $transfer): AssetTransferResource
    {
        return new AssetTransferResource(
            $transfer->load('asset:id,reference,designation,type')
        );
    }

    /**
     * Approbation : applique la mutation au bien.
     * INTERDIT : approuver sa propre demande (séparation des tâches).
     */
    public function approve(Request $request, AssetTransfer $transfer): AssetTransferResource|JsonResponse
    {
        $reviewerId = AuthenticateViaAuthService::userId($request);

        if (! $transfer->isPending()) {
            return response()->json([
                'message' => 'Cette demande a déjà été traitée.',
            ], 422);
        }

        if ($transfer->requested_by === $reviewerId) {
            return response()->json([
                'message' => 'Vous ne pouvez pas approuver votre propre demande.',
            ], 403);
        }

        DB::transaction(function () use ($transfer, $reviewerId) {
            $transfer->update([
                'status' => AssetTransfer::STATUS_APPROUVEE,
                'reviewed_by' => $reviewerId,
                'decided_at' => now(),
            ]);

            // La mutation n'est appliquée QU'APRÈS approbation
            $updates = array_filter([
                'direction_id' => $transfer->to_direction_id,
                'service_id' => $transfer->to_service_id,
                'custodian_user_id' => $transfer->to_custodian_user_id,
            ], fn ($v) => $v !== null);

            if ($updates) {
                $transfer->asset()->update($updates);
            }
        });

        Notifier::send(
            $transfer->requested_by,
            'transfert_approuve',
            'Transfert approuvé',
            'Le bien '.$transfer->asset->reference.' a été transféré.',
            '/patrimoine/'.$transfer->asset_id
        );

        return new AssetTransferResource(
            $transfer->fresh()->load('asset:id,reference,designation,type')
        );
    }

    /**
     * Rejet : motif obligatoire (validé par RejectTransferRequest).
     */
    public function reject(RejectTransferRequest $request, AssetTransfer $transfer): AssetTransferResource|JsonResponse
    {
        $reviewerId = AuthenticateViaAuthService::userId($request);

        if (! $transfer->isPending()) {
            return response()->json([
                'message' => 'Cette demande a déjà été traitée.',
            ], 422);
        }

        $transfer->update([
            'status' => AssetTransfer::STATUS_REJETEE,
            'reviewed_by' => $reviewerId,
            'decision_motif' => $request->validated('motif'),
            'decided_at' => now(),
        ]);

        Notifier::send(
            $transfer->requested_by,
            'transfert_rejete',
            'Transfert rejeté',
            'Motif : '.$request->validated('motif'),
            '/patrimoine/'.$transfer->asset_id
        );

        return new AssetTransferResource(
            $transfer->fresh()->load('asset:id,reference,designation,type')
        );
    }

    /**
     * Annulation par le demandeur, uniquement tant que la demande
     * est en attente. L'historique est conservé (statut annulee).
     */
    public function cancel(Request $request, AssetTransfer $transfer): AssetTransferResource|JsonResponse
    {
        $userId = AuthenticateViaAuthService::userId($request);

        if ($transfer->requested_by !== $userId) {
            return response()->json([
                'message' => 'Seul le demandeur peut annuler cette demande.',
            ], 403);
        }

        if (! $transfer->isPending()) {
            return response()->json([
                'message' => 'Seule une demande en attente peut être annulée.',
            ], 422);
        }

        $transfer->update(['status' => AssetTransfer::STATUS_ANNULEE]);

        return new AssetTransferResource(
            $transfer->fresh()->load('asset:id,reference,designation,type')
        );
    }
}
