@extends('layouts.app')

@section('title', ($detail['designation'] ?? 'Bien').' — PatriGest')

@section('content')
@php $d = $detail; @endphp
@if (session('success'))
    <div class="mb-4 rounded-lg border border-success/40 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
@endif
<div class="mb-6">
    <a href="{{ route('patrimoine.index') }}" class="text-sm text-primary hover:underline">← Retour au patrimoine</a>
    <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold text-navy">{{ $d['designation'] ?? 'Bien' }}</h1>
            <span class="font-mono text-sm text-muted">{{ $d['reference'] ?? '' }}</span>
            <x-status-badge :status="$d['status'] ?? ''" />
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($canEdit && in_array($d['status'] ?? '', ['brouillon', 'actif', 'hors_service']))
                <a href="{{ route('patrimoine.edit', $d['id']) }}" class="btn-secondary text-sm">Modifier</a>
            @endif
            @if ($canAssign)
                <a href="{{ route('affectations.create', ['asset_id' => $d['id']]) }}" class="btn-secondary text-sm">Affecter</a>
            @endif
            @if ($canTransfer)
                <a href="{{ route('transferts.create', ['asset_id' => $d['id']]) }}" class="btn-secondary text-sm">Transférer</a>
            @endif
            @if ($canSubmit && ($d['status'] ?? '') === 'brouillon')
                <form method="POST" action="{{ url('/patrimoine/'.$d['id'].'/submit') }}">
                    @csrf
                    <button class="btn-primary text-sm">Soumettre pour validation</button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
    <div class="space-y-4 xl:col-span-2">
        {{-- Informations --}}
        <div class="card">
            <h2 class="mb-4 text-base font-bold text-navy">Informations</h2>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm md:grid-cols-3">
                <div><dt class="text-muted">Catégorie</dt><dd class="font-medium">{{ ucfirst($d['type'] ?? '') }}</dd></div>
                <div><dt class="text-muted">Sous-catégorie</dt><dd class="font-medium">{{ $d['category'] ?? '—' }}</dd></div>
                <div><dt class="text-muted">État</dt><dd class="font-medium">{{ $d['etat'] ?? '—' }}</dd></div>
                <div><dt class="text-muted">Date d'acquisition</dt><dd class="font-medium">{{ $d['acquisition_date'] ? \Illuminate\Support\Carbon::parse($d['acquisition_date'])->format('d/m/Y') : '—' }}</dd></div>
                <div><dt class="text-muted">Valeur</dt><dd class="font-medium">{{ $d['value'] ? number_format($d['value'], 0, ',', ' ') : '—' }} FCFA</dd></div>
                <div><dt class="text-muted">Nature de la valeur</dt><dd class="font-medium">{{ ucfirst($d['value_type'] ?? '—') }}</dd></div>
                <div><dt class="text-muted">Origine / provenance</dt><dd class="font-medium">{{ $d['origin'] ?? '—' }}</dd></div>
                <div><dt class="text-muted">Description</dt><dd class="font-medium">{{ $d['description'] ?? '—' }}</dd></div>
            </dl>

            @if (!empty($d['details']))
                <h3 class="mb-2 mt-5 text-sm font-bold text-navy">Caractéristiques</h3>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm md:grid-cols-3">
                    @foreach ($d['details'] as $key => $value)
                        @if (!is_array($value) && $key !== 'id' && $key !== 'asset_id')
                            <div><dt class="text-muted">{{ ucwords(str_replace('_', ' ', $key)) }}</dt><dd class="font-medium">{{ $value ?? '—' }}</dd></div>
                        @endif
                    @endforeach
                </dl>
            @endif
        </div>

        {{-- Localisation actuelle + historique --}}
        <div class="card">
            <h2 class="mb-4 text-base font-bold text-navy">Localisation</h2>
            @if (!empty($d['current_location']))
                @php $loc = $d['current_location']; @endphp
                <p class="text-sm">
                    {{ collect([$loc['commune'] ?? null, $loc['prefecture'] ?? null, $loc['region'] ?? null])->filter()->implode(', ') ?: '—' }}
                    @if (!empty($loc['adresse'])) — {{ $loc['adresse'] }} @endif
                    @if (!empty($loc['site'])) ({{ $loc['site'] }}) @endif
                </p>
                @if (!empty($loc['latitude']) && !empty($loc['longitude']))
                    <p class="mt-1 text-xs text-muted">GPS : {{ $loc['latitude'] }}, {{ $loc['longitude'] }}</p>
                @endif
            @else
                <p class="text-sm text-muted">Aucune localisation renseignée.</p>
            @endif

            @if (!empty($tabs['locations']))
                <h3 class="mb-2 mt-5 text-sm font-bold text-navy">Historique des localisations</h3>
                <table class="w-full">
                    <tbody>
                        @foreach ($tabs['locations'] as $l)
                            <tr class="border-b border-border/60 text-sm last:border-0">
                                <td class="py-2">{{ collect([$l['commune'] ?? null, $l['site'] ?? null])->filter()->implode(' — ') }}</td>
                                <td class="py-2 text-muted">{{ $l['effective_from'] ? \Illuminate\Support\Carbon::parse($l['effective_from'])->format('d/m/Y') : '' }} → {{ $l['effective_to'] ? \Illuminate\Support\Carbon::parse($l['effective_to'])->format('d/m/Y') : 'en cours' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Documents --}}
        <div class="card">
            <h2 class="mb-4 text-base font-bold text-navy">Documents</h2>
            @forelse ($tabs['documents'] as $doc)
                <div class="flex items-center justify-between border-b border-border/60 py-2 text-sm last:border-0">
                    <span>{{ $doc['name'] }}</span>
                    <span class="flex items-center gap-3">
                        <span class="text-xs text-muted">{{ ucfirst($doc['type']) }}</span>
                        <a href="{{ route('documents.download', $doc['id']) }}" class="text-xs font-medium text-primary hover:underline">Télécharger</a>
                    </span>
                </div>
            @empty
                <p class="text-sm text-muted">Aucun document.</p>
            @endforelse
        </div>

        {{-- Incidents & maintenances --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="card">
                <h2 class="mb-3 text-base font-bold text-navy">Incidents</h2>
                @forelse ($tabs['incidents'] as $inc)
                    <div class="flex items-center justify-between border-b border-border/60 py-2 text-sm last:border-0">
                        <span>{{ ucfirst($inc['type']) }}</span>
                        <x-status-badge :status="$inc['status']" />
                    </div>
                @empty
                    <p class="text-sm text-muted">Aucun incident.</p>
                @endforelse
            </div>
            <div class="card">
                <h2 class="mb-3 text-base font-bold text-navy">Maintenances</h2>
                @forelse ($tabs['maintenances'] as $mnt)
                    <div class="flex items-center justify-between border-b border-border/60 py-2 text-sm last:border-0">
                        <span>{{ ucfirst($mnt['type']) }}</span>
                        <x-status-badge :status="$mnt['status']" />
                    </div>
                @empty
                    <p class="text-sm text-muted">Aucune maintenance.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Panneau latéral --}}
    <div class="space-y-4">
        <div class="card">
            <h2 class="mb-3 text-base font-bold text-navy">Affectation</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-muted">Service</dt><dd class="font-medium">{{ $d['service_id'] ? 'Service #'.$d['service_id'] : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Direction</dt><dd class="font-medium">{{ $d['direction_id'] ? 'Direction #'.$d['direction_id'] : '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Dépositaire</dt><dd class="font-medium">{{ $d['custodian_user_id'] ? 'Utilisateur #'.$d['custodian_user_id'] : 'Non affecté' }}</dd></div>
            </dl>
        </div>
        <div class="card">
            <h2 class="mb-3 text-base font-bold text-navy">QR code du bien</h2>
            <div class="flex justify-center rounded-lg border border-border bg-white p-3">
                <img src="{{ route('patrimoine.qr', $d['id']) }}" alt="QR code {{ $d['reference'] ?? '' }}" class="h-40 w-40">
            </div>
            <p class="mt-2 break-all text-center font-mono text-[11px] text-muted">{{ $d['qr_token'] ?? '—' }}</p>
            <p class="mt-2 text-xs text-muted">Dernière modification : {{ $d['updated_at'] ? \Illuminate\Support\Carbon::parse($d['updated_at'])->format('d/m/Y') : '—' }}</p>
        </div>
    </div>
</div>
@endsection
