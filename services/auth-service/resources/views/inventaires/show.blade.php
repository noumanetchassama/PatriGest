@extends('layouts.app')

@section('title', ($inventory['name'] ?? 'Inventaire').' — PatriGest')

@section('content')
@php $inv = $inventory; @endphp
@if (session('success'))
    <div class="mb-4 rounded-lg border border-success/40 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('inventaires.index') }}" class="text-sm text-primary hover:underline">← Retour aux inventaires</a>
        <div class="mt-2 flex items-center gap-3">
            <h1 class="text-2xl font-bold text-navy">{{ $inv['name'] }}</h1>
            <x-status-badge :status="$inv['status']" />
        </div>
        @if ($inv['description'])<p class="mt-1 text-sm text-muted">{{ $inv['description'] }}</p>@endif
    </div>
    @if ($canManage && ($inv['status'] ?? '') === 'en_cours')
        <form method="POST" action="{{ route('inventaires.close', $inv['id']) }}" onsubmit="return confirm('Clôturer la campagne ? Tous les biens doivent être vérifiés.');">
            @csrf
            <button class="btn-primary">Clôturer la campagne</button>
        </form>
    @endif
</div>

<div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
    <div class="xl:col-span-2">
        <div class="card overflow-x-auto p-0">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-border">
                        <th class="th">Bien</th>
                        <th class="th">Statut</th>
                        <th class="th">Vérifié le</th>
                        @if ($canManage && ($inv['status'] ?? '') === 'en_cours')<th class="th text-right">Actions</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-b border-border/60 last:border-0">
                            <td class="td">
                                <div class="font-medium">{{ $item['asset_reference'] ?? 'Bien #'.$item['asset_id'] }}</div>
                                <div class="text-xs text-muted">{{ $item['asset_designation'] ?? '' }}</div>
                                @if ($item['notes'])<div class="mt-1 text-xs text-muted">Note : {{ $item['notes'] }}</div>@endif
                            </td>
                            <td class="td"><x-status-badge :status="$item['status']" /></td>
                            <td class="td text-muted">{{ $item['checked_at'] ? \Illuminate\Support\Carbon::parse($item['checked_at'])->format('d/m/Y H:i') : '—' }}</td>
                            @if ($canManage && ($inv['status'] ?? '') === 'en_cours')
                            <td class="td text-right">
                                @if (($item['status'] ?? '') === 'en_attente')
                                    <div class="flex justify-end gap-1">
                                        @foreach (['trouve' => 'Trouvé', 'absent' => 'Absent', 'non_concerne' => 'N/C'] as $st => $lbl)
                                            <form method="POST" action="{{ route('inventaires.check', $item['id']) }}">
                                                @csrf
                                                <input type="hidden" name="status" value="{{ $st }}">
                                                <button class="rounded-lg border border-border px-2 py-1 text-xs font-medium hover:bg-primary-light">{{ $lbl }}</button>
                                            </form>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="4" class="td py-8 text-center text-muted">Aucun bien dans cette campagne.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($canManage && ($inv['status'] ?? '') === 'en_cours')
    <div class="card h-fit">
        <h2 class="mb-3 text-base font-bold text-navy">Ajouter des biens</h2>
        <form method="POST" action="{{ route('inventaires.items', $inv['id']) }}">
            @csrf
            <select name="asset_ids[]" multiple size="8" class="input text-sm">
                @foreach ($assets as $a)
                    <option value="{{ $a['id'] }}">{{ $a['reference'] ?? '#'.$a['id'] }} — {{ $a['designation'] ?? '' }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-muted">Ctrl+clic pour sélectionner plusieurs biens (les doublons sont ignorés).</p>
            <button class="btn-secondary mt-3 w-full text-sm">Ajouter à la campagne</button>
        </form>
    </div>
    @endif
</div>
@endsection
