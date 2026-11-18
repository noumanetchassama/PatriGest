@extends('layouts.app')

@section('title', 'Rapports — PatriGest')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-navy">Rapports et statistiques</h1>
        <p class="mt-1 text-sm text-muted">Vue d'ensemble consolidée du patrimoine</p>
    </div>
    @if ($canExport ?? false)
        <div class="flex gap-2">
            <a href="{{ route('rapports.export', 'overview') }}" class="btn-secondary text-sm">Synthèse CSV</a>
            <a href="{{ route('rapports.export', 'assets') }}" class="btn-secondary text-sm">Biens CSV</a>
            <a href="{{ route('rapports.pdf', 'overview') }}" class="btn-secondary text-sm">Synthèse PDF</a>
            <a href="{{ route('rapports.pdf', 'assets') }}" class="btn-secondary text-sm">Biens PDF</a>
        </div>
    @endif
</div>

@if (! $overview)
    <div class="rounded-lg border border-warning/40 bg-warning/10 px-4 py-3 text-sm">Les rapports sont momentanément indisponibles.</div>
@else
@php
    $a = $overview['assets'];
    $typeLabels = ['immobilier' => 'Immobiliers', 'mobilier' => 'Mobiliers', 'vehicule' => 'Véhicules'];
@endphp

<div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
    <div class="card">
        <div class="text-[13px] font-medium text-muted">Total des biens</div>
        <div class="mt-1 text-3xl font-bold text-navy">{{ number_format($a['total'], 0, ',', ' ') }}</div>
        <div class="mt-1 text-xs text-muted">{{ number_format($a['total_value'], 0, ',', ' ') }} FCFA</div>
    </div>
    <div class="card">
        <div class="text-[13px] font-medium text-muted">Biens par type</div>
        <ul class="mt-2 space-y-1 text-sm">
            @foreach ($typeLabels as $t => $label)
                <li class="flex justify-between"><span>{{ $label }}</span><span class="font-semibold">{{ $a['by_type'][$t] ?? 0 }}</span></li>
            @endforeach
        </ul>
    </div>
    <div class="card">
        <div class="text-[13px] font-medium text-muted">Workflows en attente</div>
        <ul class="mt-2 space-y-1 text-sm">
            <li class="flex justify-between"><span>Affectations</span><span class="font-semibold">{{ $overview['workflows']['assignments_pending'] }}</span></li>
            <li class="flex justify-between"><span>Transferts</span><span class="font-semibold">{{ $overview['workflows']['transfers_pending'] }}</span></li>
            <li class="flex justify-between"><span>Créations</span><span class="font-semibold">{{ $overview['workflows']['submissions_pending'] }}</span></li>
        </ul>
    </div>
    <div class="card">
        <div class="text-[13px] font-medium text-muted">Incidents & maintenance</div>
        <ul class="mt-2 space-y-1 text-sm">
            <li class="flex justify-between"><span>Incidents ouverts</span><span class="font-semibold">{{ $overview['incidents']['ouverts'] }}</span></li>
            <li class="flex justify-between"><span>Maintenances en cours</span><span class="font-semibold">{{ $overview['maintenances']['en_cours'] }}</span></li>
            <li class="flex justify-between"><span>Coût maintenance</span><span class="font-semibold">{{ number_format($overview['maintenances']['cout_total_terminées'], 0, ',', ' ') }} F</span></li>
        </ul>
    </div>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="card">
        <h2 class="mb-3 text-base font-bold text-navy">Inventaires</h2>
        <ul class="space-y-1 text-sm">
            <li class="flex justify-between"><span>Campagnes en cours</span><span class="font-semibold">{{ $overview['inventories']['en_cours'] }}</span></li>
            <li class="flex justify-between"><span>Campagnes clôturées</span><span class="font-semibold">{{ $overview['inventories']['clos'] }}</span></li>
            <li class="flex justify-between"><span>Biens absents constatés</span><span class="font-semibold text-danger">{{ $overview['inventories']['biens_absents'] }}</span></li>
        </ul>
    </div>
    <div class="card">
        <h2 class="mb-3 text-base font-bold text-navy">Répartition par statut</h2>
        <ul class="space-y-1 text-sm">
            @foreach ($a['by_status'] as $status => $count)
                <li class="flex justify-between">
                    <span>{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                    <span class="font-semibold">{{ $count }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endif
@endsection
