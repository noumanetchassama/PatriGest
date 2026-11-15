<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\AssetApiClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $api = AssetApiClient::forSession($request);

        // Tous les appels partent en parallèle (pool HTTP).
        $responses = $api->poolGet([
            '/api/v1/assets/stats' => [],
            '/api/v1/reports/overview' => [],
            '/api/v1/assignments' => ['status' => 'en_attente', 'per_page' => 10],
            '/api/v1/transfers' => ['status' => 'en_attente', 'per_page' => 10],
            '/api/v1/asset-submissions' => ['status' => 'en_attente', 'per_page' => 10],
        ]);

        $statsResponse = $responses['/api/v1/assets/stats'] ?? null;
        $overviewResponse = $responses['/api/v1/reports/overview'] ?? null;

        $stats = $statsResponse?->successful() ? $statsResponse->json('data') : null;
        $overview = $overviewResponse?->successful() ? $overviewResponse->json('data') : null;

        $pendingOps = [];
        foreach ([
            '/api/v1/assignments' => 'Affectation',
            '/api/v1/transfers' => 'Transfert',
            '/api/v1/asset-submissions' => 'Création',
        ] as $path => $type) {
            foreach (($responses[$path] ?? null)?->json('data') ?? [] as $item) {
                $pendingOps[] = [
                    'reference' => '#'.$item['id'],
                    'type' => $type,
                    'demandeur' => $item['requested_by'] ?? null,
                    'date' => $item['created_at'] ?? null,
                    'status' => $item['status'] ?? 'en_attente',
                ];
            }
        }

        $statsError = ($stats === null && $overview === null)
            ? 'Le service patrimoine est momentanément indisponible.'
            : null;

        return view('dashboard', compact('stats', 'statsError', 'overview', 'pendingOps'));
    }
}
