@extends('layouts.app')

@section('title', 'Nouvelle campagne d\'inventaire — PatriGest')

@section('content')
<div class="mb-6">
    <a href="{{ route('inventaires.index') }}" class="text-sm text-primary hover:underline">← Retour aux inventaires</a>
    <h1 class="mt-2 text-2xl font-bold text-navy">Nouvelle campagne d'inventaire</h1>
    <p class="mt-1 text-sm text-muted">Contrôle physique des biens — vous pourrez ajouter des biens après création.</p>
</div>

@if ($errors->any())
    <div class="mb-4 rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('inventaires.store') }}" class="space-y-4">
    @csrf

    <div class="card max-w-2xl space-y-4">
        <div>
            <label class="label">Nom de la campagne <span class="text-danger">*</span></label>
            <input name="name" value="{{ old('name') }}" class="input" required placeholder="Ex. : Inventaire annuel 2026">
        </div>
        <div>
            <label class="label">Description</label>
            <textarea name="description" rows="2" class="input">{{ old('description') }}</textarea>
        </div>
    </div>

    <div class="card">
        <h2 class="mb-3 text-base font-bold text-navy">Biens à inventorier <span class="text-sm font-normal text-muted">(optionnel)</span></h2>
        <div class="grid max-h-72 gap-1 overflow-y-auto sm:grid-cols-2">
            @foreach ($assets as $a)
                <label class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-primary-light/50">
                    <input type="checkbox" name="asset_ids[]" value="{{ $a['id'] }}" class="accent-primary" @checked(in_array($a['id'], old('asset_ids', [])))>
                    <span class="font-medium">{{ $a['reference'] ?? '#'.$a['id'] }}</span>
                    <span class="truncate text-muted">{{ $a['designation'] ?? '' }}</span>
                </label>
            @endforeach
        </div>
    </div>

    <div class="flex gap-3">
        <button class="btn-primary">Créer la campagne</button>
        <a href="{{ route('inventaires.index') }}" class="btn-secondary">Annuler</a>
    </div>
</form>
@endsection
