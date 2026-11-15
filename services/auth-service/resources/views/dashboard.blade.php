@extends('layouts.app')

@section('title', 'Tableau de bord — PatriGest')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-navy">Tableau de bord</h1>
    <p class="mt-1 text-sm text-muted">Vue globale du patrimoine public — {{ now()->format('Y') }}</p>
</div>

@if ($statsError)
    <div class="mb-6 rounded-lg border border-warning/40 bg-warning/10 px-4 py-3 text-sm text-ink">
        {{ $statsError }}
    </div>
@endif

@php
    $a = $overview['assets'] ?? ($stats ?? []);
    $byType = $a['by_type'] ?? ($stats['by_type'] ?? []);
    $byStatus = $a['by_status'] ?? ($stats['by_status'] ?? []);
    $totalAssets = $a['total'] ?? array_sum($byType);
    $pending = ($overview['workflows']['assignments_pending'] ?? 0)
        + ($overview['workflows']['transfers_pending'] ?? 0)
        + ($overview['workflows']['submissions_pending'] ?? 0);
    $interventions = ($overview['incidents']['ouverts'] ?? 0) + ($overview['maintenances']['en_cours'] ?? 0);
    $totalValue = $a['total_value'] ?? null;
    $typeLabels = ['immobilier' => 'Immobiliers', 'mobilier' => 'Mobiliers', 'vehicule' => 'Véhicules'];
    $typeColors = ['immobilier' => 'bg-primary', 'mobilier' => 'bg-navy', 'vehicule' => 'bg-warning'];
    $maxType = max(1, ...array_values($byType) ?: [1]);
    $statusLabels = ['actif' => 'Actifs', 'brouillon' => 'Brouillons', 'en_validation' => 'En validation', 'maintenance' => 'Maintenance', 'hors_service' => 'Hors service', 'reforme' => 'Réformés'];
    $statusColors = ['actif' => 'bg-success', 'brouillon' => 'bg-track', 'en_validation' => 'bg-warning', 'maintenance' => 'bg-warning', 'hors_service' => 'bg-danger', 'reforme' => 'bg-danger'];
    $maxStatus = max(1, ...array_values($byStatus) ?: [1]);
@endphp

{{-- Cartes statistiques --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="card">
        <div class="text-[13px] font-medium text-muted">Total des biens</div>
        <div class="mt-1 text-3xl font-bold text-navy">{{ number_format($totalAssets, 0, ',', ' ') }}</div>
        @if ($totalValue !== null)
            <div class="mt-1 text-xs text-muted">{{ number_format($totalValue, 0, ',', ' ') }} FCFA</div>
        @endif
    </div>
    <div class="card">
        <div class="text-[13px] font-medium text-muted">Demandes en attente</div>
        <div class="mt-1 text-3xl font-bold text-warning">{{ $pending }}</div>
        <a href="{{ route('validations.index') }}" class="mt-1 inline-block text-xs font-medium text-primary hover:underline">Voir les validations →</a>
    </div>
    <div class="card">
        <div class="text-[13px] font-medium text-muted">Interventions requises</div>
        <div class="mt-1 text-3xl font-bold text-danger">{{ $interventions }}</div>
        <div class="mt-1 text-xs text-muted">Incidents ouverts + maintenances en cours</div>
    </div>
    <div class="card">
        <div class="text-[13px] font-medium text-muted">Inventaires</div>
        <div class="mt-1 text-3xl font-bold text-navy">{{ $overview['inventories']['en_cours'] ?? 0 }}</div>
        <div class="mt-1 text-xs text-muted">campagne(s) en cours · {{ $overview['inventories']['clos'] ?? 0 }} clôturée(s)</div>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
    {{-- Répartition par catégorie --}}
    <div class="card">
        <h2 class="mb-4 text-base font-bold text-navy">Répartition par catégorie</h2>
        <div class="space-y-3">
            @forelse ($byType as $type => $count)
                <div>
                    <div class="mb-1 flex justify-between text-sm">
                        <span class="font-medium">{{ $typeLabels[$type] ?? ucfirst($type) }}</span>
                        <span class="text-muted">{{ number_format($count, 0, ',', ' ') }}</span>
                    </div>
                    <div class="h-2.5 rounded-full bg-track">
                        <div class="h-2.5 rounded-full {{ $typeColors[$type] ?? 'bg-primary' }}" style="width: {{ round($count / $maxType * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-muted">Aucune donnée.</p>
            @endforelse
        </div>
    </div>

    {{-- État général des biens --}}
    <div class="card">
        <h2 class="mb-4 text-base font-bold text-navy">État général des biens</h2>
        <div class="space-y-3">
            @forelse ($byStatus as $status => $count)
                <div>
                    <div class="mb-1 flex justify-between text-sm">
                        <span class="font-medium">{{ $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</span>
                        <span class="text-muted">{{ round($count / max(1, $totalAssets) * 100) }} %</span>
                    </div>
                    <div class="h-2.5 rounded-full bg-track">
                        <div class="h-2.5 rounded-full {{ $statusColors[$status] ?? 'bg-primary' }}" style="width: {{ round($count / $maxStatus * 100) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-muted">Aucune donnée.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Opérations en attente --}}
<div class="card mt-4">
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-base font-bold text-navy">Opérations en attente</h2>
        <a href="{{ route('validations.index') }}" class="text-sm font-medium text-primary hover:underline">Centre de validation →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-border">
                    <th class="th">Référence</th>
                    <th class="th">Type</th>
                    <th class="th">Soumise le</th>
                    <th class="th">Statut</th>
                    <th class="th text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendingOps as $op)
                    <tr class="border-b border-border/60 last:border-0">
                        <td class="td font-medium">{{ $op['reference'] }}</td>
                        <td class="td">{{ $op['type'] }}</td>
                        <td class="td">{{ $op['date'] ? \Illuminate\Support\Carbon::parse($op['date'])->format('d/m/Y') : '—' }}</td>
                        <td class="td"><x-status-badge :status="$op['status']" /></td>
                        <td class="td text-right"><a href="{{ route('validations.index') }}" class="text-sm font-medium text-primary hover:underline">Examiner</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="td py-6 text-center text-muted">Aucune opération en attente.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
