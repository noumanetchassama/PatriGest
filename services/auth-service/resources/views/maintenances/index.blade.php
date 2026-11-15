@extends('layouts.app')

@section('title', 'Maintenance — PatriGest')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-navy">Maintenance</h1>
        <p class="mt-1 text-sm text-muted">Suivi des interventions sur les biens</p>
    </div>
    @if ($canManage ?? false)
        <a href="{{ route('maintenance.create') }}" class="btn-primary">+ Planifier une maintenance</a>
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
            @foreach (['planifiee' => 'Planifiée', 'en_cours' => 'En cours', 'terminee' => 'Terminée', 'annulee' => 'Annulée'] as $v => $l)
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
                <th class="th">Planifiée le</th>
                <th class="th">Coût (FCFA)</th>
                <th class="th">Statut</th>
                @if ($canManage ?? false)<th class="th text-right">Actions</th>@endif
            </tr>
        </thead>
        <tbody>
            @forelse ($maintenances as $m)
                <tr class="border-b border-border/60 last:border-0">
                    <td class="td font-medium">#{{ $m['id'] }}</td>
                    <td class="td">{{ $m['asset_reference'] ?? 'Bien #'.($m['asset_id'] ?? '?') }}</td>
                    <td class="td">{{ ucfirst($m['type']) }}</td>
                    <td class="td text-muted">{{ $m['scheduled_date'] ? \Illuminate\Support\Carbon::parse($m['scheduled_date'])->format('d/m/Y') : '—' }}</td>
                    <td class="td">{{ $m['cost'] !== null ? number_format($m['cost'], 0, ',', ' ') : '—' }}</td>
                    <td class="td"><x-status-badge :status="$m['status']" /></td>
                    @if ($canManage ?? false)
                    <td class="td text-right">
                        <div class="flex justify-end gap-1">
                            @if (($m['status'] ?? '') === 'planifiee')
                                <form method="POST" action="{{ route('maintenance.action', [$m['id'], 'start']) }}">
                                    @csrf
                                    <button class="rounded-lg border border-border px-2 py-1 text-xs font-medium hover:bg-primary-light">Démarrer</button>
                                </form>
                                <form method="POST" action="{{ route('maintenance.action', [$m['id'], 'cancel']) }}" onsubmit="return confirm('Annuler cette maintenance ?');">
                                    @csrf
                                    <button class="rounded-lg border border-danger/40 px-2 py-1 text-xs font-medium text-danger hover:bg-danger/10">Annuler</button>
                                </form>
                            @elseif (($m['status'] ?? '') === 'en_cours')
                                <form method="POST" action="{{ route('maintenance.action', [$m['id'], 'complete']) }}" class="flex gap-1"
                                      onsubmit="const c=prompt('Coût réel FCFA (optionnel) :'); if(c===null) return false; if(c)this.querySelector('[name=cost]').value=c; return true;">
                                    @csrf
                                    <input type="hidden" name="cost" value="">
                                    <button class="rounded-lg bg-primary px-2 py-1 text-xs font-medium text-white hover:bg-navy">Terminer</button>
                                </form>
                                <form method="POST" action="{{ route('maintenance.action', [$m['id'], 'cancel']) }}" onsubmit="return confirm('Annuler cette maintenance ?');">
                                    @csrf
                                    <button class="rounded-lg border border-danger/40 px-2 py-1 text-xs font-medium text-danger hover:bg-danger/10">Annuler</button>
                                </form>
                            @endif
                        </div>
                    </td>
                    @endif
                </tr>
            @empty
                <tr><td colspan="{{ ($canManage ?? false) ? 7 : 6 }}" class="td py-8 text-center text-muted">Aucune maintenance.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('partials.pagination', ['meta' => $meta])
@endsection
