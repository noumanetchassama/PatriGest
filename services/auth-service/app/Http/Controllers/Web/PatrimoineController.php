<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Structure;
use App\Models\User;
use App\Support\AssetApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatrimoineController extends Controller
{
    public function index(Request $request): View
    {
        $api = AssetApiClient::forSession($request);

        $query = array_filter([
            'search' => $request->input('q'),
            'type' => $request->input('type'),
            'status' => $request->input('status'),
            'page' => $request->input('page', 1),
            'per_page' => 15,
        ]);

        $response = $api->get('/api/v1/assets', $query);
        $result = $response?->successful()
            ? AssetApiClient::collection($response)
            : ['items' => [], 'meta' => []];

        return view('patrimoine.index', [
            'assets' => $result['items'],
            'meta' => $result['meta'],
            'filters' => $request->only(['q', 'type', 'status']),
            'apiError' => $response === null || ! $response->successful(),
            'canCreate' => $request->user()->can('create-assets'),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('create-assets'), 403);

        $structures = Structure::where('is_active', true)->orderBy('name')->get();

        return view('patrimoine.create', [
            'directions' => $structures->where('type', Structure::TYPE_DIRECTION)->values(),
            'services' => $structures->where('type', Structure::TYPE_SERVICE)->values(),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'username']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('create-assets'), 403);

        $data = $request->validate([
            'type' => 'required|in:immobilier,mobilier,vehicule',
            'designation' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'category' => 'nullable|string|max:100',
            'acquisition_date' => 'nullable|date',
            'origin' => 'nullable|string|max:255',
            'value' => 'nullable|numeric|min:0',
            'value_type' => 'nullable|in:acquisition,actuelle,estimee',
            'etat' => 'nullable|in:excellent,bon,moyen,mauvais',
            'direction_id' => 'nullable|integer|min:1',
            'service_id' => 'nullable|integer|min:1',
            'custodian_user_id' => 'nullable|integer|min:1',
            // Détails par type
            'real_estate' => 'nullable|array',
            'real_estate.*' => 'nullable',
            'movable' => 'nullable|array',
            'movable.*' => 'nullable',
            'vehicle' => 'nullable|array',
            'vehicle.*' => 'nullable',
            // Localisation initiale
            'location' => 'nullable|array',
            'location.*' => 'nullable',
            // Documents (étape 4)
            'documents' => 'nullable|array|max:10',
            'documents.*.file' => 'nullable|file|max:20480',
            'documents.*.type' => 'nullable|string|max:50',
            'documents.*.name' => 'nullable|string|max:255',
            'action' => 'nullable|in:draft,submit',
        ]);

        $payload = $this->buildAssetPayload($data);

        $api = AssetApiClient::forSession($request);
        $response = $api->post('/api/v1/assets', $payload);

        if ($response === null || ! $response->successful()) {
            $errors = $response?->json('errors') ?? $response?->json('message');

            return back()->withInput()->withErrors([
                'api' => is_array($errors)
                    ? collect($errors)->flatten()->implode(' ')
                    : ($errors ?: "Le service patrimoine est indisponible. Veuillez réessayer."),
            ]);
        }

        $assetId = $response->json('data.id');

        // Étape 4 : upload des documents
        foreach ($request->allFiles()['documents'] ?? [] as $i => $doc) {
            $file = is_array($doc) ? ($doc['file'] ?? null) : $doc;
            if (! $file instanceof \Illuminate\Http\UploadedFile || ! $file->isValid()) {
                continue;
            }
            $api->upload("/api/v1/assets/{$assetId}/documents", $file, array_filter([
                'type' => $request->input("documents.{$i}.type", 'autre'),
                'name' => $request->input("documents.{$i}.name"),
            ]));
        }

        // Action finale : brouillon (défaut) ou soumission pour validation
        if (($data['action'] ?? 'draft') === 'submit') {
            $api->post("/api/v1/assets/{$assetId}/submit");
        }

        return redirect()->route('patrimoine.show', $assetId)
            ->with('success', $data['action'] === 'submit'
                ? 'Bien enregistré et soumis pour validation.'
                : 'Bien enregistré comme brouillon.');
    }

    public function submit(Request $request, int $asset): RedirectResponse
    {
        abort_unless($request->user()->can('submit-assets'), 403);

        $response = AssetApiClient::forSession($request)->post("/api/v1/assets/{$asset}/submit");

        if ($response === null || ! $response->successful()) {
            return back()->withErrors([
                'api' => $response?->json('message') ?: "Le service patrimoine est indisponible.",
            ]);
        }

        return back()->with('success', 'Bien soumis pour validation.');
    }

    /** QR code SVG du bien (encodage de l'URL de la fiche). */
    public function qr(Request $request, int $asset)
    {
        $response = AssetApiClient::forSession($request)->get("/api/v1/assets/{$asset}");
        abort_unless($response?->successful(), 404);

        $url = route('patrimoine.show', $asset);

        $renderer = new \BaconQrCode\Renderer\Image\SvgImageBackEnd;
        $writer = new \BaconQrCode\Writer(
            new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(320),
                $renderer
            )
        );

        return response($writer->writeString($url), 200, ['Content-Type' => 'image/svg+xml']);
    }

    public function show(Request $request, int $asset): View
    {
        $api = AssetApiClient::forSession($request);

        $response = $api->get("/api/v1/assets/{$asset}");
        abort_unless($response?->successful(), 404);

        $detail = $response->json('data');

        // Onglets chargés en parallèle
        $paths = [
            'locations' => '/api/v1/assets/'.$asset.'/locations',
            'documents' => '/api/v1/assets/'.$asset.'/documents',
            'incidents' => '/api/v1/incidents?asset_id='.$asset,
            'maintenances' => '/api/v1/maintenances?asset_id='.$asset,
        ];
        // Les 3 derniers passent par query string — poolGet envoie des query params,
        // on les passe dans la query à la place.
        $responses = $api->poolGet([
            $paths['locations'] => [],
            $paths['documents'] => [],
            '/api/v1/incidents' => ['asset_id' => $asset],
            '/api/v1/maintenances' => ['asset_id' => $asset],
        ]);

        $tabs = [];
        $tabs['locations'] = ($responses[$paths['locations']] ?? null)?->successful()
            ? ($responses[$paths['locations']]->json('data') ?? []) : [];
        $tabs['documents'] = ($responses[$paths['documents']] ?? null)?->successful()
            ? ($responses[$paths['documents']]->json('data') ?? []) : [];
        $tabs['incidents'] = ($responses['/api/v1/incidents'] ?? null)?->successful()
            ? ($responses['/api/v1/incidents']->json('data') ?? []) : [];
        $tabs['maintenances'] = ($responses['/api/v1/maintenances'] ?? null)?->successful()
            ? ($responses['/api/v1/maintenances']->json('data') ?? []) : [];

        return view('patrimoine.show', compact('detail', 'tabs') + [
            'canEdit' => $request->user()->can('edit-assets'),
            'canAssign' => $request->user()->can('create-assignments'),
            'canTransfer' => $request->user()->can('create-transfer'),
            'canSubmit' => $request->user()->can('submit-assets'),
        ]);
    }

    public function edit(Request $request, int $asset): View
    {
        abort_unless($request->user()->can('edit-assets'), 403);

        $api = AssetApiClient::forSession($request);
        $response = $api->get("/api/v1/assets/{$asset}");
        abort_unless($response?->successful(), 404);

        $detail = $response->json('data');
        $structures = Structure::where('is_active', true)->orderBy('name')->get();

        return view('patrimoine.edit', [
            'detail' => $detail,
            'directions' => $structures->where('type', Structure::TYPE_DIRECTION)->values(),
            'services' => $structures->where('type', Structure::TYPE_SERVICE)->values(),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'username']),
        ]);
    }

    public function update(Request $request, int $asset): RedirectResponse
    {
        abort_unless($request->user()->can('edit-assets'), 403);

        $data = $request->validate([
            // `type` sert uniquement à choisir le sous-objet ; l'API l'ignore à l'update
            'type' => 'nullable|in:immobilier,mobilier,vehicule',
            'designation' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'category' => 'nullable|string|max:100',
            'acquisition_date' => 'nullable|date',
            'origin' => 'nullable|string|max:255',
            'value' => 'nullable|numeric|min:0',
            'value_type' => 'nullable|in:acquisition,actuelle,estimee',
            'etat' => 'nullable|in:excellent,bon,moyen,mauvais',
            'direction_id' => 'nullable|integer|min:1',
            'service_id' => 'nullable|integer|min:1',
            'custodian_user_id' => 'nullable|integer|min:1',
            'status' => 'nullable|in:brouillon,actif,maintenance,hors_service,reforme,perdu',
            'real_estate' => 'nullable|array',
            'real_estate.*' => 'nullable',
            'movable' => 'nullable|array',
            'movable.*' => 'nullable',
            'vehicle' => 'nullable|array',
            'vehicle.*' => 'nullable',
        ]);

        $response = AssetApiClient::forSession($request)->put("/api/v1/assets/{$asset}", $this->buildAssetPayload($data));

        if ($response === null || ! $response->successful()) {
            $errors = $response?->json('errors') ?? $response?->json('message');

            return back()->withInput()->withErrors([
                'api' => is_array($errors)
                    ? collect($errors)->flatten()->implode(' ')
                    : ($errors ?: "Le service patrimoine est indisponible. Veuillez réessayer."),
            ]);
        }

        return redirect()->route('patrimoine.show', $asset)->with('success', 'Bien mis à jour.');
    }

    /**
     * Construit le payload API : champs communs + sous-objet du type concerné.
     * `type` est ignoré par l'API à l'update (absent des règles validées).
     */
    private function buildAssetPayload(array $data): array
    {
        $payload = collect($data)->only([
            'type', 'designation', 'description', 'category', 'acquisition_date',
            'origin', 'value', 'value_type', 'etat', 'direction_id', 'service_id',
            'custodian_user_id', 'status',
        ])->filter(fn ($v) => $v !== null && $v !== '')->all();

        $typeKey = isset($data['type'])
            ? (['immobilier' => 'real_estate', 'mobilier' => 'movable', 'vehicule' => 'vehicle'][$data['type']] ?? null)
            : null;
        if ($typeKey && ! empty($data[$typeKey])) {
            $payload[$typeKey] = collect($data[$typeKey])->filter(fn ($v) => $v !== null && $v !== '')->all();
        }

        if (! empty($data['location'])) {
            $payload['location'] = collect($data['location'])->filter(fn ($v) => $v !== null && $v !== '')->all();
        }

        return $payload;
    }
}
