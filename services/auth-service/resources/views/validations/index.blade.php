@extends('layouts.app')

@section('title', 'Centre de validation — PatriGest')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-navy">Centre de validation</h1>
    <p class="mt-1 text-sm text-muted">Demandes en attente d'examen</p>
</div>

@if (session('success'))
    <div class="mb-4 rounded-lg border border-success/40 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
@endif

@php
    $sections = [
        'submissions' => ['Création de biens', 'submission'],
        'assignments' => ['Affectations', 'assignment'],
        'transfers' => ['Transferts', 'transfer'],
    ];
@endphp

@foreach ($sections as $key => [$title, $kind])
<div class="card mb-4 overflow-x-auto p-0">
    <div class="border-b border-border px-5 py-3">
        <h2 class="text-base font-bold text-navy">{{ $title }} <span class="ml-1 text-sm font-normal text-muted">({{ count($queues[$key]) }})</span></h2>
    </div>
    <table class="w-full">
        <thead>
            <tr class="border-b border-border">
                <th class="th">#</th>
                <th class="th">Bien</th>
                <th class="th">Demandeur</th>
                <th class="th">Motif</th>
                <th class="th">Soumise le</th>
                <th class="th text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($queues[$key] as $item)
                <tr class="border-b border-border/60 last:border-0 align-top">
                    <td class="td font-medium">#{{ $item['id'] }}</td>
                    <td class="td">{{ $item['asset_reference'] ?? 'Bien #'.($item['asset_id'] ?? '?') }}</td>
                    <td class="td">{{ $item['requested_by'] ? 'Utilisateur #'.$item['requested_by'] : '—' }}</td>
                    <td class="td text-muted">{{ $item['motif'] ?? '—' }}</td>
                    <td class="td text-muted">{{ $item['created_at'] ? \Illuminate\Support\Carbon::parse($item['created_at'])->format('d/m/Y H:i') : '—' }}</td>
                    <td class="td">
                        @if ($can[$key] ?? false)
                            @if ((int) ($item['requested_by'] ?? 0) === (int) $me)
                                <span class="text-xs text-muted" title="Vous ne pouvez pas traiter votre propre demande.">Auto-approbation interdite</span>
                            @else
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('validations.decide', [$kind, $item['id']]) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="approve">
                                        <button class="btn px-3! py-1.5! text-xs bg-success text-white hover:opacity-90">Approuver</button>
                                    </form>
                                    <form method="POST" action="{{ route('validations.decide', [$kind, $item['id']]) }}" class="flex items-center gap-1">
                                        @csrf
                                        <input type="hidden" name="action" value="reject">
                                        <input name="motif" required placeholder="Motif du rejet" class="input w-36! py-1.5! text-xs">
                                        <button class="btn px-3! py-1.5! text-xs bg-danger text-white hover:opacity-90">Rejeter</button>
                                    </form>
                                </div>
                            @endif
                        @else
                            <span class="text-xs text-muted">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="td py-6 text-center text-muted">Aucune demande en attente.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endforeach
@endsection
