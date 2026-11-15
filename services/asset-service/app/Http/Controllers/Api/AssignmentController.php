<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateViaAuthService;
use App\Http\Requests\RejectAssignmentRequest;
use App\Http\Requests\StoreAssignmentRequest;
use App\Http\Resources\AssetAssignmentResource;
use App\Models\Asset;
use App\Models\AssetAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $assignments = AssetAssignment::query()
            ->with('asset:id,reference,designation,type')
            ->when($request->filled('asset_id'), fn ($q) => $q->where('asset_id', $request->integer('asset_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('custodian_user_id'), fn ($q) => $q->where('custodian_user_id', $request->integer('custodian_user_id')))
            ->when($request->filled('requested_by'), fn ($q) => $q->where('requested_by', $request->integer('requested_by')))
            ->latest()
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return AssetAssignmentResource::collection($assignments);
    }

    public function store(StoreAssignmentRequest $request): JsonResponse
    {
        $userId = AuthenticateViaAuthService::userId($request);

        $assignment = DB::transaction(function () use ($request, $userId) {
            $asset = Asset::findOrFail($request->integer('asset_id'));

            // Une seule demande en attente par bien à la fois
            if ($asset->pendingAssignment()->exists()) {
                throw ValidationException::withMessages([
                    'asset_id' => 'Une demande d\'affectation est déjà en attente pour ce bien.',
                ]);
            }

            return AssetAssignment::create([
                'asset_id' => $asset->id,
                'custodian_user_id' => $request->integer('custodian_user_id'),
                'requested_by' => $userId,
                'status' => AssetAssignment::STATUS_EN_ATTENTE,
                'motif' => $request->validated('motif'),
            ]);
        });

        return (new AssetAssignmentResource(
            $assignment->load('asset:id,reference,designation,type')
        ))->response()->setStatusCode(201);
    }

    public function show(AssetAssignment $assignment): AssetAssignmentResource
    {
        return new AssetAssignmentResource(
            $assignment->load('asset:id,reference,designation,type')
        );
    }

    /**
     * Approbation : met à jour le dépositaire du bien.
     * INTERDIT : approuver sa propre demande (séparation des tâches).
     */
    public function approve(Request $request, AssetAssignment $assignment): AssetAssignmentResource|JsonResponse
    {
        $reviewerId = AuthenticateViaAuthService::userId($request);

        if (! $assignment->isPending()) {
            return response()->json([
                'message' => 'Cette demande a déjà été traitée.',
            ], 422);
        }

        if ($assignment->requested_by === $reviewerId) {
            return response()->json([
                'message' => 'Vous ne pouvez pas approuver votre propre demande.',
            ], 403);
        }

        DB::transaction(function () use ($assignment, $reviewerId) {
            $assignment->update([
                'status' => AssetAssignment::STATUS_APPROUVEE,
                'reviewed_by' => $reviewerId,
                'decided_at' => now(),
            ]);

            // Le dépositaire n'est modifié QU'APRÈS approbation
            $assignment->asset()->update([
                'custodian_user_id' => $assignment->custodian_user_id,
            ]);
        });

        return new AssetAssignmentResource(
            $assignment->fresh()->load('asset:id,reference,designation,type')
        );
    }

    /**
     * Rejet : motif obligatoire (validé par RejectAssignmentRequest).
     */
    public function reject(RejectAssignmentRequest $request, AssetAssignment $assignment): AssetAssignmentResource|JsonResponse
    {
        $reviewerId = AuthenticateViaAuthService::userId($request);

        if (! $assignment->isPending()) {
            return response()->json([
                'message' => 'Cette demande a déjà été traitée.',
            ], 422);
        }

        $assignment->update([
            'status' => AssetAssignment::STATUS_REJETEE,
            'reviewed_by' => $reviewerId,
            'decision_motif' => $request->validated('motif'),
            'decided_at' => now(),
        ]);

        return new AssetAssignmentResource(
            $assignment->fresh()->load('asset:id,reference,designation,type')
        );
    }

    /**
     * Annulation par le demandeur, uniquement tant que la demande
     * est en attente. L'historique est conservé (statut annulee).
     */
    public function cancel(Request $request, AssetAssignment $assignment): AssetAssignmentResource|JsonResponse
    {
        $userId = AuthenticateViaAuthService::userId($request);

        if ($assignment->requested_by !== $userId) {
            return response()->json([
                'message' => 'Seul le demandeur peut annuler cette demande.',
            ], 403);
        }

        if (! $assignment->isPending()) {
            return response()->json([
                'message' => 'Seule une demande en attente peut être annulée.',
            ], 422);
        }

        $assignment->update(['status' => AssetAssignment::STATUS_ANNULEE]);

        return new AssetAssignmentResource(
            $assignment->fresh()->load('asset:id,reference,designation,type')
        );
    }
}
