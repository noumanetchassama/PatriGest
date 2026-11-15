@extends('layouts.app')

@section('title', 'Transferts — PatriGest')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-navy">Transferts de biens</h1>
        <p class="mt-1 text-sm text-muted">Suivi des demandes de transfert entre structures</p>
    </div>
    @if ($canCreate ?? false)
        <a href="{{ route('transferts.create') }}" class="btn-primary">+ Nouveau transfert</a>
    @endif
</div>

@if (session('success'))
    <div class="mb-4 rounded-lg border border-success/40 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
@endif

@if ($apiError)
    <div class="mb-4 rounded-lg border border-warning/40 bg-warning/10 px-4 py-3 text-sm">Le service patrimoine est momentanément indisponible.</div>
@endif

<form method="GET" class="card mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="label">Statut</label>
        <select name="status" class="input">
            <option value="">Tous</option>
            @foreach (['en_attente' => 'En attente', 'approuvee' => 'Approuvé', 'rejetee' => 'Rejeté', 'annulee' => 'Annulé'] as $v => $l)
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
                <th class="th">Destination</th>
                <th class="th">Motif</th>
                <th class="th">Statut</th>
                <th class="th">Décision</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transfers as $t)
                <tr class="border-b border-border/60 last:border-0">
                    <td class="td font-medium">#{{ $t['id'] }}</td>
                    <td class="td">{{ $t['asset_reference'] ?? 'Bien #'.($t['asset_id'] ?? '?') }}</td>
                    <td class="td text-muted">
                        {{ collect([$t['to_direction_id'] ?? null, $t['to_service_id'] ?? null, $t['to_custodian_user_id'] ?? null])->filter(fn($v) => $v !== null)->map(fn($v) => '#'.$v)->implode(' · ') ?: '—' }}
                    </td>
                    <td class="td text-muted">{{ $t['motif'] ?? '—' }}</td>
                    <td class="td"><x-status-badge :status="$t['status']" /></td>
                    <td class="td text-xs text-muted">{{ $t['decision_motif'] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="td py-8 text-center text-muted">Aucun transfert.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('partials.pagination', ['meta' => $meta])
@endsection
