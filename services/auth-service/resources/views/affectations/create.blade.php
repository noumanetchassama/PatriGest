@extends('layouts.app')

@section('title', 'Nouvelle affectation — PatriGest')

@section('content')
<div class="mb-6">
    <a href="{{ route('affectations.index') }}" class="text-sm text-primary hover:underline">← Retour aux affectations</a>
    <h1 class="mt-2 text-2xl font-bold text-navy">Nouvelle demande d'affectation</h1>
    <p class="mt-1 text-sm text-muted">La demande devra être approuvée avant que le dépositaire soit modifié.</p>
</div>

@if ($errors->any())
    <div class="mb-4 rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('affectations.store') }}" class="card max-w-2xl space-y-4">
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

    <div>
        <label class="label">Nouveau dépositaire <span class="text-danger">*</span></label>
        <select name="custodian_user_id" class="input" required>
            <option value="">— Choisir un utilisateur —</option>
            @foreach ($users as $u)
                <option value="{{ $u->id }}" @selected((string) old('custodian_user_id') === (string) $u->id)>{{ $u->name }}{{ $u->username ? ' ('.$u->username.')' : '' }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="label">Motif de la demande</label>
        <textarea name="motif" rows="3" class="input" placeholder="Raison de l'affectation…">{{ old('motif') }}</textarea>
    </div>

    <div class="flex gap-3">
        <button class="btn-primary">Envoyer pour validation</button>
        <a href="{{ route('affectations.index') }}" class="btn-secondary">Annuler</a>
    </div>
</form>
@endsection
