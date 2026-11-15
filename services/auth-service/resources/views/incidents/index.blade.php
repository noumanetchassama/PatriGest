@extends('layouts.app')

@section('title', 'Incidents — PatriGest')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-navy">Inspections et incidents</h1>
        <p class="mt-1 text-sm text-muted">Déclaration et suivi des anomalies</p>
    </div>
    @if ($canManage ?? false)
        <a href="{{ route('incidents.create') }}" class="btn-primary">+ Déclarer un incident</a>
    @endif
</div>

@if (session('success'))
    <div class="mb-4 rounded-lg border border-success/40 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

@if ($apiError)
    <div class="mb-4 rounded-lg border border-warning/40 bg-warning/10 px-4 py-3 text-sm">Le service patrimoine est momentanément indisponible.</div>
@endif

<form method="GET" class="card mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="label">Statut</label>
        <select name="status" class="input">
            <option value="">Tous</option>
            @foreach (['ouvert' => 'Ouvert', 'en_cours' => 'En cours', 'resolu' => 'Résolu', 'clos' => 'Clos'] as $v => $l)
                <option value="{{ $v }}" @selected(($status ?? '') === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn-primary">Filtrer</button>
</form>

<div class="card overflow-x-auto p-0">
    <table class="w-full">
        <thead>
            <tr class="border-b border-border">
                <th class="th">#</th>
                <th class="th">Bien</th>
                <th class="th">Type</th>
                <th class="th">Gravité</th>
                <th class="th">Déclaré le</th>
                <th class="th">Statut</th>
                @if ($canManage ?? false)<th class="th text-right">Actions</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse ($incidents as $inc)
                <tr class="border-b border-border/60 last:border-0">
                    <td class="td font-medium">#{{ $inc['id'] }}</td>
                    <td class="td">{{ $inc['asset_reference'] ?? 'Bien #'.($inc['asset_id'] ?? '?') }}</td>
                    <td class="td">{{ ucfirst($inc['type']) }}</td>
                    <td class="td">{{ ucfirst($inc['severity'] ?? 'mineure') }}</td>
                    <td class="td text-muted">{{ $inc['occurred_at'] ? \Illuminate\Support\Carbon::parse($inc['occurred_at'])->format('d/m/Y') : '—' }}</td>
                    <td class="td"><x-status-badge :status="$inc['status']" /></td>
                    @if ($canManage ?? false)
                    <td class="td text-right">
                        <div class="flex justify-end gap-1">
                            @if (in_array($inc['status'] ?? '', ['ouvert', 'en_cours']))
                                <form method="POST" action="{{ route('incidents.resolve', $inc['id']) }}" class="flex gap-1"
                                      onsubmit="const n=prompt('Notes de résolution (optionnel) :'); if(n===null) return false; this.querySelector('[name=resolution_notes]').value=n; return true;">
                                    @csrf
                                    <input type="hidden" name="resolution_notes" value="">
                                    <button class="rounded-lg border border-border px-2 py-1 text-xs font-medium hover:bg-primary-light">Résoudre</button>
                                </form>
                            @endif
                            @if (($inc['status'] ?? '') === 'resolu')
                                <form method="POST" action="{{ route('incidents.close', $inc['id']) }}">
                                    @csrf
                                    <button class="rounded-lg bg-primary px-2 py-1 text-xs font-medium text-white hover:bg-navy">Clôturer</button>
                                </form>
                            @endif
                        </div>
                    </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ ($canManage ?? false) ? 7 : 6 }}" class="td py-8 text-center text-muted">Aucun incident.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('partials.pagination', ['meta' => $meta])
@endsection
