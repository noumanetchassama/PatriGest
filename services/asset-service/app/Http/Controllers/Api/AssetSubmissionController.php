<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateViaAuthService;
use App\Http\Requests\RejectAssetSubmissionRequest;
use App\Http\Requests\StoreAssetSubmissionRequest;
use App\Http\Resources\AssetSubmissionResource;
use App\Models\Asset;
use App\Support\Notifier;
use App\Models\AssetSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetSubmissionController extends Controller
{
    /**
     * Soumet un bien brouillon à validation.
     * Le statut du bien passe à `en_validation` ; la décision
     * (actif / brouillon) n'intervient qu'après revue.
     */
    public function store(StoreAssetSubmissionRequest $request, Asset $asset): JsonResponse
    {
        $userId = AuthenticateViaAuthService::userId($request);

        $submission = DB::transaction(function () use ($request, $asset, $userId) {
            if ($asset->status !== Asset::STATUS_BROUILLON) {
                throw ValidationException::withMessages([
                    'asset_id' => 'Seul un bien au statut « brouillon » peut être soumis.',
                ]);
            }

            if ($asset->pendingSubmission()->exists()) {
                throw ValidationException::withMessages([
                    'asset_id' => 'Une soumission est déjà en attente pour ce bien.',
                ]);
            }

            $asset->update(['status' => Asset::STATUS_EN_VALIDATION]);

            return AssetSubmission::create([
                'asset_id' => $asset->id,
                'requested_by' => $userId,
                'status' => AssetSubmission::STATUS_EN_ATTENTE,
                'motif' => $request->validated('motif'),
            ]);
        });

        return (new AssetSubmissionResource(
            $submission->load('asset:id,reference,designation,type')
        ))->response()->setStatusCode(201);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $submissions = AssetSubmission::query()
            ->with('asset:id,reference,designation,type')
            ->when($request->filled('asset_id'), fn ($q) => $q->where('asset_id', $request->integer('asset_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('requested_by'), fn ($q) => $q->where('requested_by', $request->integer('requested_by')))
            ->latest()
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return AssetSubmissionResource::collection($submissions);
    }

    public function show(AssetSubmission $submission): AssetSubmissionResource
    {
        return new AssetSubmissionResource(
            $submission->load('asset:id,reference,designation,type')
        );
    }

    /**
     * Approbation : le bien passe à `actif`.
     * INTERDIT : approuver sa propre soumission.
     */
    public function approve(Request $request, AssetSubmission $submission): AssetSubmissionResource|JsonResponse
    {
        $reviewerId = AuthenticateViaAuthService::userId($request);

        if (! $submission->isPending()) {
            return response()->json([
                'message' => 'Cette soumission a déjà été traitée.',
            ], 422);
        }

        if ($submission->requested_by === $reviewerId) {
            return response()->json([
                'message' => 'Vous ne pouvez pas approuver votre propre soumission.',
            ], 403);
        }

        DB::transaction(function () use ($submission, $reviewerId) {
            $submission->update([
                'status' => AssetSubmission::STATUS_APPROUVEE,
                'reviewed_by' => $reviewerId,
                'decided_at' => now(),
            ]);

            // Le passage à « actif » n'intervient QU'APRÈS approbation
            $submission->asset()->update(['status' => Asset::STATUS_ACTIF]);
        });

        Notifier::send(
            $submission->requested_by,
            'soumission_approuvee',
            'Création de bien approuvée',
            'Le bien '.$submission->asset->reference.' ('.$submission->asset->designation.') est maintenant actif.',
            '/patrimoine/'.$submission->asset_id
        );

        return new AssetSubmissionResource(
            $submission->fresh()->load('asset:id,reference,designation,type,status')
        );
    }

    /**
     * Rejet : motif obligatoire ; le bien repasse à `brouillon`
     * pour permettre correction et nouvelle soumission.
     */
    public function reject(RejectAssetSubmissionRequest $request, AssetSubmission $submission): AssetSubmissionResource|JsonResponse
    {
        $reviewerId = AuthenticateViaAuthService::userId($request);

        if (! $submission->isPending()) {
            return response()->json([
                'message' => 'Cette soumission a déjà été traitée.',
            ], 422);
        }

        DB::transaction(function () use ($request, $submission, $reviewerId) {
            $submission->update([
                'status' => AssetSubmission::STATUS_REJETEE,
                'reviewed_by' => $reviewerId,
                'decision_motif' => $request->validated('motif'),
                'decided_at' => now(),
            ]);

            $submission->asset()->update(['status' => Asset::STATUS_BROUILLON]);
        });

        Notifier::send(
            $submission->requested_by,
            'soumission_rejetee',
            'Création de bien rejetée',
            'Motif : '.$request->validated('motif'),
            '/patrimoine/'.$submission->asset_id
        );

        return new AssetSubmissionResource(
            $submission->fresh()->load('asset:id,reference,designation,type,status')
        );
    }
}
