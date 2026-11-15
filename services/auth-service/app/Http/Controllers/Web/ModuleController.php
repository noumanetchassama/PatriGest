<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Structure;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Support\AssetApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ModuleController extends Controller
{
    /** Inventaires (campagnes en cours + historique). */
    public function inventaires(Request $request): View
    {
        $view = $this->assetList($request, 'inventaires.index', '/api/v1/inventories', 'inventories');
        $view->with('canManage', $request->user()->can('manage-inventory'));

        return $view;
    }

    public function incidents(Request $request): View
    {
        $api = AssetApiClient::forSession($request);

        $res = $api->get('/api/v1/incidents', [
            'status' => $request->input('status'),
            'page' => $request->input('page', 1),
            'per_page' => 15,
        ]);
        $items = $res?->successful() ? ($res->json('data') ?? []) : [];
        $meta = $res?->successful() ? ($res->json('meta') ?? []) : [];

        return view('incidents.index', [
            'incidents' => $items,
            'meta' => $meta,
            'status' => $request->input('status'),
            'apiError' => $res === null || ! $res->successful(),
            'canManage' => $request->user()->can('manage-incidents'),
        ]);
    }

    public function maintenances(Request $request): View
    {
        $api = AssetApiClient::forSession($request);

        $res = $api->get('/api/v1/maintenances', [
            'status' => $request->input('status'),
            'page' => $request->input('page', 1),
            'per_page' => 15,
        ]);
        $items = $res?->successful() ? ($res->json('data') ?? []) : [];
        $meta = $res?->successful() ? ($res->json('meta') ?? []) : [];

        return view('maintenances.index', [
            'maintenances' => $items,
            'meta' => $meta,
            'status' => $request->input('status'),
            'apiError' => $res === null || ! $res->successful(),
            'canManage' => $request->user()->can('manage-maintenance'),
        ]);
    }

    public function documents(Request $request): View
    {
        $api = AssetApiClient::forSession($request);

        $res = $api->get('/api/v1/documents', [
            'type' => $request->input('type'),
            'page' => $request->input('page', 1),
            'per_page' => 15,
        ]);
        $items = $res?->successful() ? ($res->json('data') ?? []) : [];
        $meta = $res?->successful() ? ($res->json('meta') ?? []) : [];

        return view('documents.index', [
            'documents' => $items,
            'meta' => $meta,
            'type' => $request->input('type'),
            'apiError' => $res === null || ! $res->successful(),
        ]);
    }

    public function rapports(Request $request): View
    {
        $api = AssetApiClient::forSession($request);

        $overview = null;
        $overviewRes = $api->get('/api/v1/reports/overview');
        if ($overviewRes?->successful()) {
            $overview = $overviewRes->json('data');
        }

        return view('rapports.index', [
            'overview' => $overview,
            'canExport' => $request->user()->can('view-reports'),
        ]);
    }

    /** Export CSV : `assets` (liste complète) ou `overview` (synthèse). */
    public function rapportsExport(Request $request, string $type): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless($request->user()->can('view-reports'), 403);
        abort_unless(in_array($type, ['assets', 'overview'], true), 404);

        $api = AssetApiClient::forSession($request);

        if ($type === 'assets') {
            $rows = [[
                'reference', 'designation', 'type', 'categorie', 'statut', 'etat',
                'valeur_fcfa', 'nature_valeur', 'date_acquisition', 'origine',
                'direction_id', 'service_id', 'createur_id', 'cree_le',
            ]];

            for ($page = 1; $page <= 50; $page++) {
                $res = $api->get('/api/v1/assets', ['per_page' => 100, 'page' => $page]);
                abort_unless($res?->successful(), 502, 'Service patrimoine indisponible.');

                foreach ($res->json('data') ?? [] as $a) {
                    $rows[] = [
                        $a['reference'] ?? '', $a['designation'] ?? '', $a['type'] ?? '',
                        $a['category'] ?? '', $a['status'] ?? '', $a['etat'] ?? '',
                        $a['value'] ?? '', $a['value_type'] ?? '',
                        $a['acquisition_date'] ?? '', $a['origin'] ?? '',
                        $a['direction_id'] ?? '', $a['service_id'] ?? '', $a['custodian_user_id'] ?? '',
                        $a['created_at'] ?? '',
                    ];
                }

                if ($page >= (int) ($res->json('meta.last_page') ?? 1)) {
                    break;
                }
            }

            return $this->csvResponse('patrimoine_'.now()->format('Ymd_His').'.csv', $rows);
        }

        // Synthèse : lignes indicateur / valeur
        $res = $api->get('/api/v1/reports/overview');
        abort_unless($res?->successful(), 502, 'Service patrimoine indisponible.');
        $o = $res->json('data') ?? [];

        $rows = [['indicateur', 'valeur']];
        $rows[] = ['Total des biens', $o['assets']['total'] ?? 0];
        $rows[] = ['Valeur totale (FCFA)', $o['assets']['total_value'] ?? 0];
        foreach ($o['assets']['by_type'] ?? [] as $k => $v) {
            $rows[] = ['Biens — '.$k, $v];
        }
        foreach ($o['assets']['by_status'] ?? [] as $k => $v) {
            $rows[] = ['Statut — '.$k, $v];
        }
        $rows[] = ['Affectations en attente', $o['workflows']['assignments_pending'] ?? 0];
        $rows[] = ['Transferts en attente', $o['workflows']['transfers_pending'] ?? 0];
        $rows[] = ['Créations en attente', $o['workflows']['submissions_pending'] ?? 0];
        $rows[] = ['Incidents ouverts', $o['incidents']['ouverts'] ?? 0];
        $rows[] = ['Maintenances en cours', $o['maintenances']['en_cours'] ?? 0];
        $rows[] = ['Coût maintenance terminé (FCFA)', $o['maintenances']['cout_total_terminées'] ?? 0];
        $rows[] = ['Inventaires en cours', $o['inventories']['en_cours'] ?? 0];
        $rows[] = ['Inventaires clôturés', $o['inventories']['clos'] ?? 0];
        $rows[] = ['Biens absents constatés', $o['inventories']['biens_absents'] ?? 0];
        $rows[] = ['Documents au total', $o['documents_total'] ?? 0];

        return $this->csvResponse('rapport_synthese_'.now()->format('Ymd_His').'.csv', $rows);
    }

    private function csvResponse(string $filename, array $rows): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, $row, ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function notifications(Request $request): View
    {
        // Notifications consolidées depuis les files de validation (données réelles).
        $api = AssetApiClient::forSession($request);
        $items = [];

        $paths = [
            'validation' => '/api/v1/asset-submissions',
            'affectation' => '/api/v1/assignments',
            'transfert' => '/api/v1/transfers',
        ];

        $responses = $api->poolGet(array_fill_keys($paths, ['status' => 'en_attente', 'per_page' => 10]));

        foreach ($paths as $label => $path) {
            foreach (($responses[$path] ?? null)?->json('data') ?? [] as $item) {
                $items[] = [
                    'label' => ucfirst($label).' en attente de validation',
                    'detail' => '#'.$item['id'],
                    'date' => $item['created_at'] ?? null,
                ];
            }
        }

        return view('notifications.index', ['items' => $items]);
    }

    public function administration(Request $request): View
    {
        return view('administration.index', [
            'users' => User::with('roles')->latest()->paginate(15),
            'roles' => Role::withCount('users')->get(),
            'structures' => Structure::orderBy('type')->get(),
        ]);
    }

    public function profil(Request $request): View
    {
        return view('profil.show', ['user' => $request->user()]);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Mot de passe mis à jour.');
    }

    // -----------------------------------------------------------------
    // Inventaires
    // -----------------------------------------------------------------

    public function inventoryCreate(Request $request): View
    {
        abort_unless($request->user()->can('manage-inventory'), 403);

        return view('inventaires.create', [
            'assets' => $this->assetsOptions($request),
        ]);
    }

    public function inventoryStore(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('manage-inventory'), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'asset_ids' => 'nullable|array',
            'asset_ids.*' => 'integer|min:1',
        ]);

        $response = AssetApiClient::forSession($request)->post('/api/v1/inventories', $data);

        if ($response === null || ! $response->successful()) {
            return back()->withInput()->withErrors([
                'api' => $response?->json('message') ?: "Le service patrimoine est indisponible.",
            ]);
        }

        return redirect()->route('inventaires.show', $response->json('data.id'))
            ->with('success', 'Campagne d\'inventaire créée.');
    }

    public function inventoryShow(Request $request, int $inventory): View
    {
        $api = AssetApiClient::forSession($request);

        $invRes = $api->get("/api/v1/inventories/{$inventory}");
        abort_unless($invRes?->successful(), 404);

        $itemsRes = $api->get("/api/v1/inventories/{$inventory}/items", ['per_page' => 200]);

        return view('inventaires.show', [
            'inventory' => $invRes->json('data'),
            'items' => $itemsRes?->successful() ? ($itemsRes->json('data') ?? []) : [],
            'assets' => $this->assetsOptions($request),
            'canManage' => $request->user()->can('manage-inventory'),
            'apiError' => $itemsRes === null || ! $itemsRes->successful(),
        ]);
    }

    public function inventoryAddItems(Request $request, int $inventory): RedirectResponse
    {
        abort_unless($request->user()->can('manage-inventory'), 403);

        $data = $request->validate([
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'integer|min:1',
        ]);

        $response = AssetApiClient::forSession($request)->post("/api/v1/inventories/{$inventory}/items", $data);

        if ($response === null || ! $response->successful()) {
            return back()->withErrors(['api' => $response?->json('message') ?: 'Ajout impossible.']);
        }

        return back()->with('success', 'Biens ajoutés à la campagne.');
    }

    public function inventoryCheckItem(Request $request, int $item): RedirectResponse
    {
        abort_unless($request->user()->can('manage-inventory'), 403);

        $data = $request->validate([
            'status' => 'required|in:trouve,absent,non_concerne',
            'notes' => 'nullable|string|max:2000',
        ]);

        $response = AssetApiClient::forSession($request)->post("/api/v1/inventory-items/{$item}/check", $data);

        if ($response === null || ! $response->successful()) {
            return back()->withErrors(['api' => $response?->json('message') ?: 'Vérification impossible.']);
        }

        return back()->with('success', 'Bien vérifié.');
    }

    public function inventoryClose(Request $request, int $inventory): RedirectResponse
    {
        abort_unless($request->user()->can('manage-inventory'), 403);

        $response = AssetApiClient::forSession($request)->post("/api/v1/inventories/{$inventory}/close");

        if ($response === null || ! $response->successful()) {
            return back()->withErrors(['api' => $response?->json('message') ?: 'Clôture impossible (biens restants à vérifier ?).']);
        }

        return redirect()->route('inventaires.index')->with('success', 'Campagne clôturée.');
    }

    // -----------------------------------------------------------------
    // Incidents
    // -----------------------------------------------------------------

    public function incidentCreate(Request $request): View
    {
        abort_unless($request->user()->can('manage-incidents'), 403);

        return view('incidents.create', [
            'assets' => $this->assetsOptions($request),
        ]);
    }

    public function incidentStore(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('manage-incidents'), 403);

        $data = $request->validate([
            'asset_id' => 'required|integer|min:1',
            'type' => 'required|in:dommage,perte,accident,vol',
            'description' => 'required|string|max:5000',
            'occurred_at' => 'nullable|date',
            'severity' => 'nullable|in:mineure,majeure,critique',
            'estimated_cost' => 'nullable|numeric|min:0',
        ]);

        $assetId = $data['asset_id'];
        unset($data['asset_id']);

        $response = AssetApiClient::forSession($request)->post("/api/v1/assets/{$assetId}/incidents", $data);

        if ($response === null || ! $response->successful()) {
            return back()->withInput()->withErrors([
                'api' => $response?->json('message') ?: "Le service patrimoine est indisponible.",
            ]);
        }

        return redirect()->route('incidents.index')->with('success', 'Incident déclaré.');
    }

    public function incidentResolve(Request $request, int $incident): RedirectResponse
    {
        abort_unless($request->user()->can('manage-incidents'), 403);

        $data = $request->validate(['resolution_notes' => 'nullable|string|max:5000']);

        $response = AssetApiClient::forSession($request)->post("/api/v1/incidents/{$incident}/resolve", $data);

        if ($response === null || ! $response->successful()) {
            return back()->withErrors(['api' => $response?->json('message') ?: 'Résolution impossible.']);
        }

        return back()->with('success', 'Incident marqué comme résolu.');
    }

    public function incidentClose(Request $request, int $incident): RedirectResponse
    {
        abort_unless($request->user()->can('manage-incidents'), 403);

        $response = AssetApiClient::forSession($request)->post("/api/v1/incidents/{$incident}/close");

        if ($response === null || ! $response->successful()) {
            return back()->withErrors(['api' => $response?->json('message') ?: 'Clôture impossible.']);
        }

        return back()->with('success', 'Incident clos.');
    }

    // -----------------------------------------------------------------
    // Maintenances
    // -----------------------------------------------------------------

    public function maintenanceCreate(Request $request): View
    {
        abort_unless($request->user()->can('manage-maintenance'), 403);

        return view('maintenances.create', [
            'assets' => $this->assetsOptions($request),
        ]);
    }

    public function maintenanceStore(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('manage-maintenance'), 403);

        $data = $request->validate([
            'asset_id' => 'required|integer|min:1',
            'type' => 'required|in:preventive,corrective',
            'description' => 'required|string|max:2000',
            'scheduled_date' => 'nullable|date',
            'cost' => 'nullable|numeric|min:0',
            'provider' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
        ]);

        $assetId = $data['asset_id'];
        unset($data['asset_id']);

        $response = AssetApiClient::forSession($request)->post("/api/v1/assets/{$assetId}/maintenances", $data);

        if ($response === null || ! $response->successful()) {
            return back()->withInput()->withErrors([
                'api' => $response?->json('message') ?: "Le service patrimoine est indisponible.",
            ]);
        }

        return redirect()->route('maintenance.index')->with('success', 'Maintenance planifiée.');
    }

    public function maintenanceAction(Request $request, int $maintenance, string $action): RedirectResponse
    {
        abort_unless($request->user()->can('manage-maintenance'), 403);
        abort_unless(in_array($action, ['start', 'complete', 'cancel'], true), 404);

        $data = [];
        if ($action === 'complete') {
            $data = $request->validate([
                'realized_date' => 'nullable|date',
                'cost' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:5000',
            ]);
        }

        $response = AssetApiClient::forSession($request)->post("/api/v1/maintenances/{$maintenance}/{$action}", $data);

        if ($response === null || ! $response->successful()) {
            return back()->withErrors(['api' => $response?->json('message') ?: 'Action impossible.']);
        }

        return back()->with('success', 'Maintenance mise à jour.');
    }

    /** Liste des biens pour les sélecteurs (100 premiers). */
    private function assetsOptions(Request $request): array
    {
        $res = AssetApiClient::forSession($request)->get('/api/v1/assets', ['per_page' => 100]);

        return $res?->successful() ? ($res->json('data') ?? []) : [];
    }

    private function assetList(Request $request, string $view, string $endpoint, string $var): View
    {
        $api = AssetApiClient::forSession($request);

        $res = $api->get($endpoint, [
            'status' => $request->input('status'),
            'page' => $request->input('page', 1),
            'per_page' => 15,
        ]);
        $items = $res?->successful() ? ($res->json('data') ?? []) : [];
        $meta = $res?->successful() ? ($res->json('meta') ?? []) : [];

        return view($view, [
            $var => $items,
            'meta' => $meta,
            'status' => $request->input('status'),
            'apiError' => $res === null || ! $res->successful(),
        ]);
    }
}
