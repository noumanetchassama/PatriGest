@extends('layouts.app')

@section('title', 'Gestion du patrimoine — PatriGest')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-navy">Gestion du patrimoine</h1>
        <p class="mt-1 text-sm text-muted">Tous les biens immobiliers, mobiliers et véhicules</p>
    </div>
    @if ($canCreate ?? false)
        <a href="{{ route('patrimoine.create') }}" class="btn-primary">+ Enregistrer un bien</a>
    @endif
</div>

@if ($apiError)
    <div class="mb-4 rounded-lg border border-warning/40 bg-warning/10 px-4 py-3 text-sm">Le service patrimoine est momentanément indisponible.</div>
@endif

{{-- Filtres --}}
<form method="GET" class="card mb-4 flex flex-wrap items-end gap-3">
    <div class="min-w-52 flex-1">
        <label class="label">Rechercher</label>
        <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Désignation, référence…" class="input">
    </div>
    <div>
        <label class="label">Catégorie</label>
        <select name="type" class="input">
            <option value="">Toutes</option>
            @foreach (['immobilier' => 'Immobilier', 'mobilier' => 'Mobilier', 'vehicule' => 'Véhicule'] as $v => $l)
                <option value="{{ $v }}" @selected(($filters['type'] ?? '') === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Statut</label>
        <select name="status" class="input">
            <option value="">Tous</option>
            @foreach (['actif' => 'Actif', 'brouillon' => 'Brouillon', 'en_validation' => 'En validation', 'maintenance' => 'Maintenance', 'hors_service' => 'Hors service', 'reforme' => 'Réformé'] as $v => $l)
                <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn-primary">Filtrer</button>
    <a href="{{ route('patrimoine.index') }}" class="btn-secondary">Réinitialiser</a>
</form>

<div class="card overflow-x-auto p-0">
    <table class="w-full">
        <thead>
            <tr class="border-b border-border">
                <th class="th">Identifiant</th>
                <th class="th">Désignation</th>
                <th class="th">Catégorie</th>
                <th class="th">Localisation</th>
                <th class="th">Statut</th>
                <th class="th text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assets as $asset)
                <tr class="border-b border-border/60 last:border-0 hover:bg-primary-light/40">
                    <td class="td font-medium">{{ $asset['reference'] ?? '—' }}</td>
                    <td class="td">{{ $asset['designation'] ?? '—' }}</td>
                    <td class="td">{{ ucfirst($asset['type'] ?? '') }}</td>
                    <td class="td text-muted">{{ $asset['current_location']['commune'] ?? $asset['current_location']['site'] ?? '—' }}</td>
                    <td class="td"><x-status-badge :status="$asset['status'] ?? ''" /></td>
                    <td class="td text-right">
                        <a href="{{ route('patrimoine.show', $asset['id']) }}" class="text-sm font-medium text-primary hover:underline">Consulter</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="td py-8 text-center text-muted">Aucun bien trouvé.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@include('partials.pagination', ['meta' => $meta])
@endsection
