@extends('layouts.app')

@section('title', 'Nouvelle maintenance — PatriGest')

@section('content')
<div class="mb-6">
    <a href="{{ route('maintenance.index') }}" class="text-sm text-primary hover:underline">← Retour à la maintenance</a>
    <h1 class="mt-2 text-2xl font-bold text-navy">Planifier une maintenance</h1>
    <p class="mt-1 text-sm text-muted">Le statut du bien passera à « maintenance » le jour de l'intervention.</p>
</div>

@if ($errors->any())
    <div class="mb-4 rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('maintenance.store') }}" class="card max-w-2xl space-y-4">
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
            <label class="label">Type <span class="text-danger">*</span></label>
            <select name="type" class="input" required>
                <option value="">—</option>
                <option value="preventive" @selected(old('type') === 'preventive')>Préventive</option>
                <option value="corrective" @selected(old('type') === 'corrective')>Corrective</option>
            </select>
        </div>
        <div>
            <label class="label">Date planifiée</label>
            <input type="date" name="scheduled_date" value="{{ old('scheduled_date') }}" class="input">
        </div>
        <div>
            <label class="label">Coût estimé (FCFA)</label>
            <input type="number" name="cost" min="0" step="1" value="{{ old('cost') }}" class="input">
        </div>
        <div>
            <label class="label">Prestataire</label>
            <input name="provider" value="{{ old('provider') }}" class="input" placeholder="Ex. : Société X">
        </div>
    </div>

    <div>
        <label class="label">Description des travaux <span class="text-danger">*</span></label>
        <textarea name="description" rows="3" class="input" required placeholder="Nature de l'intervention…">{{ old('description') }}</textarea>
    </div>

    <div>
        <label class="label">Notes</label>
        <textarea name="notes" rows="2" class="input">{{ old('notes') }}</textarea>
    </div>

    <div class="flex gap-3">
        <button class="btn-primary">Planifier</button>
        <a href="{{ route('maintenance.index') }}" class="btn-secondary">Annuler</a>
    </div>
</form>
@endsection
