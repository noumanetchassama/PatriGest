@extends('layouts.app')

@section('title', 'Déclarer un incident — PatriGest')

@section('content')
<div class="mb-6">
    <a href="{{ route('incidents.index') }}" class="text-sm text-primary hover:underline">← Retour aux incidents</a>
    <h1 class="mt-2 text-2xl font-bold text-navy">Déclarer un incident</h1>
    <p class="mt-1 text-sm text-muted">Dommage, perte, accident ou vol sur un bien du patrimoine.</p>
</div>

@if ($errors->any())
    <div class="mb-4 rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('incidents.store') }}" class="card max-w-2xl space-y-4">
    @csrf

    <div>
        <label class="label">Bien concerné <span class="text-danger">*</span></label>
        <select name="asset_id" class="input" required>
            <option value="">— Choisir un bien —</option>
            @foreach ($assets as $a)
                <option value="{{ $a['id'] }}" @selected((string) old('asset_id') === (string) $a['id'])>
                    {{ $a['reference'] ?? '#'.$a['id'] }} — {{ $a['designation'] ?? '' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label">Type d'incident <span class="text-danger">*</span></label>
            <select name="type" class="input" required>
                <option value="">—</option>
                @foreach (['dommage' => 'Dommage', 'perte' => 'Perte', 'accident' => 'Accident', 'vol' => 'Vol'] as $v => $l)
                    <option value="{{ $v }}" @selected(old('type') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Gravité</label>
            <select name="severity" class="input">
                <option value="">Mineure (défaut)</option>
                @foreach (['mineure' => 'Mineure', 'majeure' => 'Majeure', 'critique' => 'Critique'] as $v => $l)
                    <option value="{{ $v }}" @selected(old('severity', 'mineure') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Date de l'incident</label>
            <input type="datetime-local" name="occurred_at" value="{{ old('occurred_at') }}" class="input">
        </div>
        <div>
            <label class="label">Coût estimé (FCFA)</label>
            <input type="number" name="estimated_cost" min="0" step="1" value="{{ old('estimated_cost') }}" class="input">
        </div>
    </div>

    <div>
        <label class="label">Description <span class="text-danger">*</span></label>
        <textarea name="description" rows="4" class="input" required placeholder="Circonstances, dégâts constatés…">{{ old('description') }}</textarea>
    </div>

    <div class="flex gap-3">
        <button class="btn-primary">Déclarer l'incident</button>
        <a href="{{ route('incidents.index') }}" class="btn-secondary">Annuler</a>
    </div>
</form>
@endsection
