<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\AssetApiClient;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    /**
     * Téléchargement proxy : l'asset-service exige un Bearer token,
     * le navigateur n'en a pas — on relaie via la session.
     */
    public function download(Request $request, int $document): StreamedResponse
    {
        $api = AssetApiClient::forSession($request);
        $response = $api->get("/api/v1/documents/{$document}/download");

        abort_unless($response?->successful(), 404);

        // Récupère les métadonnées pour nommer le fichier
        $meta = $api->get("/api/v1/documents/{$document}");
        $name = $meta?->successful() ? ($meta->json('data.name') ?? 'document') : 'document';

        return response()->streamDownload(function () use ($response) {
            echo $response->body();
        }, $name);
    }
}
