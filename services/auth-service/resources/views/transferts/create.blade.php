@extends('layouts.app')

@section('title', 'Nouveau transfert — PatriGest')

@section('content')
<div class="mb-6">
    <a href="{{ route('transferts.index') }}" class="text-sm text-primary hover:underline">← Retour aux transferts</a>
    <h1 class="mt-2 text-2xl font-bold text-navy">Nouveau transfert de bien</h1>
    <p class="mt-1 text-sm text-muted">Indiquez au moins une cible (direction, service ou dépositaire). La mutation sera appliquée après approbation.</p>
</div>

@if ($errors->any())
    <div class="mb-4 rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('transferts.store') }}" class="card max-w-2xl space-y-4">
    @csrf

    <div>
        <label class="label">Bien <span class="text-danger">*</span></label>
        <select name="asset_id" class="input" required>
            <option value="">— Choisir un bien —</option>
            @foreach ($assets as $a)
                <option value="{{ $a['id'] }}" @selected((string) old('asset_id', $asset['id'] ?? '') === (string) $a['id'])>
                    {{ $a['reference'] ?? '#'.$a['id'] }} — {{ $a['designation'] ?? '' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label">Direction cible</label>
            <select name="to_direction_id" class="input">
                <option value="">— Inchangée —</option>
                @foreach ($directions as $d)
                    <option value="{{ $d->id }}" @selected((string) old('to_direction_id') === (string) $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Service cible</label>
            <select name="to_service_id" class="input">
                <option value="">— Inchangé —</option>
                @foreach ($services as $s)
                    <option value="{{ $s->id }}" @selected((string) old('to_service_id') === (string) $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="label">Dépositaire cible</label>
        <select name="to_custodian_user_id" class="input">
            <option value="">— Inchangé —</option>
            @foreach ($users as $u)
                <option value="{{ $u->id }}" @selected((string) old('to_custodian_user_id') === (string) $u->id)>{{ $u->name }}{{ $u->username ? ' ('.$u->username.')' : '' }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="label">Motif du transfert</label>
        <textarea name="motif" rows="3" class="input" placeholder="Raison du transfert…">{{ old('motif') }}</textarea>
    </div>

    <div class="flex gap-3">
        <button class="btn-primary">Envoyer pour validation</button>
        <a href="{{ route('transferts.index') }}" class="btn-secondary">Annuler</a>
    </div>
</form>
@endsection
