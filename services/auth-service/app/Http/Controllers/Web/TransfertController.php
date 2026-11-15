<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Structure;
use App\Models\User;
use App\Support\AssetApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransfertController extends Controller
{
    public function index(Request $request): View
    {
        $api = AssetApiClient::forSession($request);

        $query = array_filter([
            'status' => $request->input('status'),
            'page' => $request->input('page', 1),
            'per_page' => 15,
        ]);

        $response = $api->get('/api/v1/transfers', $query);
        $result = $response?->successful()
            ? AssetApiClient::collection($response)
            : ['items' => [], 'meta' => []];

        return view('transferts.index', [
            'transfers' => $result['items'],
            'meta' => $result['meta'],
            'status' => $request->input('status'),
            'apiError' => $response === null || ! $response->successful(),
            'canCreate' => $request->user()->can('create-transfer'),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('create-transfer'), 403);

        $api = AssetApiClient::forSession($request);

        $asset = null;
        if ($request->filled('asset_id')) {
            $res = $api->get('/api/v1/assets/'.$request->integer('asset_id'));
            $asset = $res?->successful() ? $res->json('data') : null;
        }

        $assetsRes = $api->get('/api/v1/assets', ['per_page' => 100]);
        $assets = $assetsRes?->successful() ? ($assetsRes->json('data') ?? []) : [];

        $structures = Structure::where('is_active', true)->orderBy('name')->get();

        return view('transferts.create', [
            'asset' => $asset,
            'assets' => $assets,
            'directions' => $structures->where('type', Structure::TYPE_DIRECTION)->values(),
            'services' => $structures->where('type', Structure::TYPE_SERVICE)->values(),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'username']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('create-transfer'), 403);

        $data = $request->validate([
            'asset_id' => 'required|integer|min:1',
            'to_direction_id' => 'nullable|integer|min:1',
            'to_service_id' => 'nullable|integer|min:1',
            'to_custodian_user_id' => 'nullable|integer|min:1',
            'motif' => 'nullable|string|max:2000',
        ]);

        if (empty($data['to_direction_id']) && empty($data['to_service_id']) && empty($data['to_custodian_user_id'])) {
            return back()->withInput()->withErrors([
                'to_custodian_user_id' => 'Indiquez au moins une cible : direction, service ou dépositaire.',
            ]);
        }

        $response = AssetApiClient::forSession($request)->post('/api/v1/transfers', array_filter($data, fn ($v) => $v !== null && $v !== ''));

        if ($response === null || ! $response->successful()) {
            return back()->withInput()->withErrors([
                'api' => $response?->json('message') ?: "Le service patrimoine est indisponible.",
            ]);
        }

        return redirect()->route('transferts.index')->with('success', 'Demande de transfert envoyée pour validation.');
    }
}
