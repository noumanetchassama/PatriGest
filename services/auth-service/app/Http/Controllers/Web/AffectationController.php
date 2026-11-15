<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AssetApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AffectationController extends Controller
{
    public function index(Request $request): View
    {
        $api = AssetApiClient::forSession($request);

        $query = array_filter([
            'status' => $request->input('status'),
            'page' => $request->input('page', 1),
            'per_page' => 15,
        ]);

        $response = $api->get('/api/v1/assignments', $query);
        $result = $response?->successful()
            ? AssetApiClient::collection($response)
            : ['items' => [], 'meta' => []];

        return view('affectations.index', [
            'assignments' => $result['items'],
            'meta' => $result['meta'],
            'status' => $request->input('status'),
            'apiError' => $response === null || ! $response->successful(),
            'canCreate' => $request->user()->can('create-assignments'),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can('create-assignments'), 403);

        $asset = null;
        if ($request->filled('asset_id')) {
            $res = AssetApiClient::forSession($request)->get('/api/v1/assets/'.$request->integer('asset_id'));
            $asset = $res?->successful() ? $res->json('data') : null;
        }

        $assetsRes = AssetApiClient::forSession($request)->get('/api/v1/assets', ['per_page' => 100]);
        $assets = $assetsRes?->successful() ? ($assetsRes->json('data') ?? []) : [];

        return view('affectations.create', [
            'asset' => $asset,
            'assets' => $assets,
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'username']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('create-assignments'), 403);

        $data = $request->validate([
            'asset_id' => 'required|integer|min:1',
            'custodian_user_id' => 'required|integer|min:1',
            'motif' => 'nullable|string|max:2000',
        ]);

        $response = AssetApiClient::forSession($request)->post('/api/v1/assignments', $data);

        if ($response === null || ! $response->successful()) {
            return back()->withInput()->withErrors([
                'api' => $response?->json('message') ?: "Le service patrimoine est indisponible.",
            ]);
        }

        return redirect()->route('affectations.index')->with('success', 'Demande d\'affectation envoyée pour validation.');
    }
}
