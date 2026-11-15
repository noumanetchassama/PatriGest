@extends('layouts.app')

@section('title', 'Inventaires — PatriGest')

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-navy">Inventaires physiques</h1>
        <p class="mt-1 text-sm text-muted">Campagnes de contrôle des biens</p>
    </div>
    @if ($canManage ?? false)
        <a href="{{ route('inventaires.create') }}" class="btn-primary">+ Nouvelle campagne</a>
    @endif
</div>

@if (session('success'))
    <div class="mb-4 rounded-lg border border-success/40 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
@endif

@if ($apiError)
    <div class="mb-4 rounded-lg border border-warning/40 bg-warning/10 px-4 py-3 text-sm">Le service patrimoine est momentanément indisponible.</div>
@endif

<div class="card overflow-x-auto p-0">
    <table class="w-full">
        <thead>
            <tr class="border-b border-border">
                <th class="th">Campagne</th>
                <th class="th">Biens à contrôler</th>
                <th class="th">En attente</th>
                <th class="th">Statut</th>
                <th class="th">Clôturée le</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($inventories as $inv)
                <tr class="border-b border-border/60 last:border-0">
                    <td class="td font-medium"><a href="{{ route('inventaires.show', $inv['id']) }}" class="text-primary hover:underline">{{ $inv['name'] }}</a></td>
                    <td class="td">{{ $inv['items_count'] ?? '—' }}</td>
                    <td class="td">{{ $inv['pending_items_count'] ?? '—' }}</td>
                    <td class="td"><x-status-badge :status="$inv['status']" /></td>
                    <td class="td text-muted">{{ $inv['closed_at'] ? \Illuminate\Support\Carbon::parse($inv['closed_at'])->format('d/m/Y') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="td py-8 text-center text-muted">Aucune campagne d'inventaire.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('partials.pagination', ['meta' => $meta])
@endsection
