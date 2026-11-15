<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\AssetApiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ValidationController extends Controller
{
    public function index(Request $request): View
    {
        $api = AssetApiClient::forSession($request);

        $paths = [
            'submissions' => '/api/v1/asset-submissions',
            'assignments' => '/api/v1/assignments',
            'transfers' => '/api/v1/transfers',
        ];

        $responses = $api->poolGet(array_fill_keys($paths, ['status' => 'en_attente', 'per_page' => 50]));

        $queues = [];
        foreach ($paths as $key => $path) {
            $res = $responses[$path] ?? null;
            $queues[$key] = $res?->successful() ? ($res->json('data') ?? []) : [];
        }

        $user = $request->user();
        $can = [
            'submissions' => $user?->can('approve-asset-creation') ?? false,
            'assignments' => $user?->can('approve-assignment') ?? false,
            'transfers' => $user?->can('approve-transfer') ?? false,
        ];

        return view('validations.index', [
            'queues' => $queues,
            'can' => $can,
            'me' => $user?->id,
        ]);
    }

    /**
     * Actions de validation relayées vers l'Asset Service.
     */
    public function decide(Request $request, string $kind, int $id): \Illuminate\Http\RedirectResponse
    {
        $api = AssetApiClient::forSession($request);

        $action = $request->input('action');
        $validActions = ['approve', 'reject', 'cancel'];
        abort_unless(in_array($action, $validActions, true), 422);

        $base = match ($kind) {
            'submission' => '/api/v1/asset-submissions/'.$id,
            'assignment' => '/api/v1/assignments/'.$id,
            'transfer' => '/api/v1/transfers/'.$id,
            default => abort(404),
        };

        $payload = [];
        if ($action === 'reject') {
            $payload['motif'] = $request->input('motif')
                ?? $request->input('decision_motif');
            if (empty($payload['motif'])) {
                return back()->with('error', 'Le motif de rejet est obligatoire.');
            }
        }

        $response = $api->post("{$base}/{$action}", $payload);

        if ($response?->successful()) {
            return back()->with('success', 'Demande traitée avec succès.');
        }

        $message = $response?->json('message') ?? 'Action impossible pour le moment.';

        return back()->with('error', $message);
    }
}
