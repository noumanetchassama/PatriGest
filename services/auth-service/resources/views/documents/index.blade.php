@extends('layouts.app')

@section('title', 'Documents — PatriGest')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-navy">Gestion documentaire</h1>
    <p class="mt-1 text-sm text-muted">Justificatifs rattachés aux biens</p>
</div>

@if ($apiError)
    <div class="mb-4 rounded-lg border border-warning/40 bg-warning/10 px-4 py-3 text-sm">Le service patrimoine est momentanément indisponible.</div>
@endif

<form method="GET" class="card mb-4 flex flex-wrap items-end gap-3">
    <div>
        <label class="label">Type</label>
        <select name="type" class="input">
            <option value="">Tous</option>
            @foreach (['facture' => 'Factures', 'contrat' => 'Contrats', 'garantie' => 'Garanties', 'carte_grise' => 'Cartes grises', 'autre' => 'Autres'] as $v => $l)
                <option value="{{ $v }}" @selected(($type ?? '') === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn-primary">Filtrer</button>
</form>

<div class="card overflow-x-auto p-0">
    <table class="w-full">
        <thead>
            <tr class="border-b border-border">
                <th class="th">Document</th>
                <th class="th">Type</th>
                <th class="th">Bien associé</th>
                <th class="th">Taille</th>
                <th class="th">Ajouté le</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($documents as $doc)
                <tr class="border-b border-border/60 last:border-0">
                    <td class="td font-medium">{{ $doc['name'] }}</td>
                    <td class="td">{{ ucfirst(str_replace('_', ' ', $doc['type'])) }}</td>
                    <td class="td">{{ $doc['asset_reference'] ?? 'Bien #'.($doc['asset_id'] ?? '?') }}</td>
                    <td class="td text-muted">{{ $doc['size'] ? number_format($doc['size'] / 1024, 0, ',', ' ').' Ko' : '—' }}</td>
                    <td class="td text-muted">{{ $doc['created_at'] ? \Illuminate\Support\Carbon::parse($doc['created_at'])->format('d/m/Y') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="td py-8 text-center text-muted">Aucun document.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('partials.pagination', ['meta' => $meta])
@endsection
