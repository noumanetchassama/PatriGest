@extends('layouts.app')

@section('title', 'Modifier le bien — PatriGest')

@section('content')
@php
    $d = $detail;
    $det = $d['details'] ?? [];
    $typeKey = ['immobilier' => 'real_estate', 'mobilier' => 'movable', 'vehicule' => 'vehicle'][$d['type'] ?? ''] ?? null;
    $dv = fn (string $k) => old($typeKey.'.'.$k, $det[$k] ?? '');
@endphp

<div class="mb-6">
    <a href="{{ route('patrimoine.show', $d['id']) }}" class="text-sm text-primary hover:underline">← Retour à la fiche</a>
    <h1 class="mt-2 text-2xl font-bold text-navy">Modifier {{ $d['designation'] ?? 'le bien' }}</h1>
    <p class="mt-1 text-sm text-muted">{{ $d['reference'] ?? '' }} — les modifications sont historisées.</p>
</div>

@if ($errors->any())
    <div class="mb-4 rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger">
        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<form method="POST" action="{{ route('patrimoine.update', $d['id']) }}" class="space-y-4">
    @csrf
    @method('PUT')
    <input type="hidden" name="type" value="{{ $d['type'] }}">

    {{-- Général --}}
    <div class="card">
        <h2 class="mb-4 text-base font-bold text-navy">Informations générales</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Désignation <span class="text-danger">*</span></label>
                <input name="designation" value="{{ old('designation', $d['designation'] ?? '') }}" class="input" required>
            </div>
            <div>
                <label class="label">Catégorie</label>
                <input name="category" value="{{ old('category', $d['category'] ?? '') }}" class="input">
            </div>
            <div class="sm:col-span-2">
                <label class="label">Description</label>
                <textarea name="description" rows="2" class="input">{{ old('description', $d['description'] ?? '') }}</textarea>
            </div>
            <div>
                <label class="label">Date d'acquisition</label>
                <input type="date" name="acquisition_date" value="{{ old('acquisition_date', $d['acquisition_date'] ? \Illuminate\Support\Carbon::parse($d['acquisition_date'])->format('Y-m-d') : '') }}" class="input">
            </div>
            <div>
                <label class="label">Valeur (FCFA)</label>
                <input type="number" name="value" min="0" step="1" value="{{ old('value', $d['value'] ?? '') }}" class="input">
            </div>
            <div>
                <label class="label">Nature de la valeur</label>
                <select name="value_type" class="input">
                    <option value="">—</option>
                    @foreach (['acquisition' => 'Acquisition', 'actuelle' => 'Valeur actuelle', 'estimee' => 'Valeur estimée'] as $v => $l)
                        <option value="{{ $v }}" @selected(old('value_type', $d['value_type'] ?? '') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Origine / provenance</label>
                <input name="origin" value="{{ old('origin', $d['origin'] ?? '') }}" class="input">
            </div>
            <div>
                <label class="label">État</label>
                <select name="etat" class="input">
                    <option value="">—</option>
                    @foreach (['excellent' => 'Excellent', 'bon' => 'Bon', 'moyen' => 'Moyen', 'mauvais' => 'Mauvais'] as $v => $l)
                        <option value="{{ $v }}" @selected(old('etat', $d['etat'] ?? '') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Statut</label>
                <select name="status" class="input">
                    @foreach (['brouillon' => 'Brouillon', 'actif' => 'Actif', 'maintenance' => 'Maintenance', 'hors_service' => 'Hors service', 'reforme' => 'Réformé', 'perdu' => 'Perdu'] as $v => $l)
                        <option value="{{ $v }}" @selected(old('status', $d['status'] ?? '') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Administratif --}}
    <div class="card">
        <h2 class="mb-4 text-base font-bold text-navy">Affectation</h2>
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="label">Direction</label>
                <select name="direction_id" class="input">
                    <option value="">—</option>
                    @foreach ($directions as $dir)
                        <option value="{{ $dir->id }}" @selected((string) old('direction_id', $d['direction_id'] ?? '') === (string) $dir->id)>{{ $dir->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Service</label>
                <select name="service_id" class="input">
                    <option value="">—</option>
                    @foreach ($services as $s)
                        <option value="{{ $s->id }}" @selected((string) old('service_id', $d['service_id'] ?? '') === (string) $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Dépositaire</label>
                <select name="custodian_user_id" class="input">
                    <option value="">—</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected((string) old('custodian_user_id', $d['custodian_user_id'] ?? '') === (string) $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <p class="mt-2 text-xs text-muted">Pour un changement de dépositaire soumis à validation, utilisez « Nouvelle affectation ».</p>
    </div>

    {{-- Caractéristiques par type --}}
    @if ($d['type'] === 'immobilier')
    <div class="card">
        <h2 class="mb-4 text-base font-bold text-navy">Informations immobilières</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label">Type de bien</label>
                <select name="real_estate[bien_type]" class="input">
                    <option value="">—</option>
                    @foreach (['terrain' => 'Terrain', 'batiment' => 'Bâtiment', 'bureau' => 'Bureau', 'logement' => 'Logement', 'infrastructure' => 'Infrastructure'] as $v => $l)
                        <option value="{{ $v }}" @selected($dv('bien_type') === $v)>{{ $l }}</option>
                    @endforeach
                </select></div>
            <div><label class="label">Référence cadastrale</label><input name="real_estate[reference_cadastrale]" value="{{ $dv('reference_cadastrale') }}" class="input"></div>
            <div><label class="label">Superficie terrain (m²)</label><input type="number" step="0.01" min="0" name="real_estate[surface_terrain]" value="{{ $dv('surface_terrain') }}" class="input"></div>
            <div><label class="label">Superficie bâtie (m²)</label><input type="number" step="0.01" min="0" name="real_estate[surface_batie]" value="{{ $dv('surface_batie') }}" class="input"></div>
            <div><label class="label">Nombre de pièces</label><input type="number" min="0" name="real_estate[nb_pieces]" value="{{ $dv('nb_pieces') }}" class="input"></div>
            <div><label class="label">Année de construction</label><input type="number" min="1800" max="2100" name="real_estate[annee_construction]" value="{{ $dv('annee_construction') }}" class="input"></div>
            <div><label class="label">Titre de possession</label><input name="real_estate[titre_possession]" value="{{ $dv('titre_possession') }}" class="input"></div>
            <div><label class="label">Type d'occupant</label><input name="real_estate[occupant_type]" value="{{ $dv('occupant_type') }}" class="input"></div>
            <div class="sm:col-span-2"><label class="label">Observations</label><textarea name="real_estate[observations]" rows="2" class="input">{{ $dv('observations') }}</textarea></div>
        </div>
    </div>
    @elseif ($d['type'] === 'mobilier')
    <div class="card">
        <h2 class="mb-4 text-base font-bold text-navy">Informations du mobilier</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label">Marque</label><input name="movable[marque]" value="{{ $dv('marque') }}" class="input"></div>
            <div><label class="label">Modèle</label><input name="movable[modele]" value="{{ $dv('modele') }}" class="input"></div>
            <div><label class="label">Numéro de série</label><input name="movable[numero_serie]" value="{{ $dv('numero_serie') }}" class="input"></div>
            <div><label class="label">Fournisseur</label><input name="movable[fournisseur]" value="{{ $dv('fournisseur') }}" class="input"></div>
            <div><label class="label">Fin de garantie</label><input type="date" name="movable[garantie_fin]" value="{{ $dv('garantie_fin') ? \Illuminate\Support\Carbon::parse($dv('garantie_fin'))->format('Y-m-d') : '' }}" class="input"></div>
            <div><label class="label">État physique</label><input name="movable[etat_physique]" value="{{ $dv('etat_physique') }}" class="input"></div>
            <div class="sm:col-span-2"><label class="label">Observations</label><textarea name="movable[observations]" rows="2" class="input">{{ $dv('observations') }}</textarea></div>
        </div>
    </div>
    @elseif ($d['type'] === 'vehicule')
    <div class="card">
        <h2 class="mb-4 text-base font-bold text-navy">Informations du véhicule</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label">Immatriculation</label><input name="vehicle[immatriculation]" value="{{ $dv('immatriculation') }}" class="input"></div>
            <div><label class="label">Marque</label><input name="vehicle[marque]" value="{{ $dv('marque') }}" class="input"></div>
            <div><label class="label">Modèle</label><input name="vehicle[modele]" value="{{ $dv('modele') }}" class="input"></div>
            <div><label class="label">Année</label><input type="number" min="1950" max="2100" name="vehicle[annee]" value="{{ $dv('annee') }}" class="input"></div>
            <div><label class="label">Carburant</label>
                <select name="vehicle[carburant]" class="input">
                    <option value="">—</option>
                    @foreach (['essence' => 'Essence', 'diesel' => 'Diesel', 'electrique' => 'Électrique', 'hybride' => 'Hybride'] as $v => $l)
                        <option value="{{ $v }}" @selected($dv('carburant') === $v)>{{ $l }}</option>
                    @endforeach
                </select></div>
            <div><label class="label">Kilométrage</label><input type="number" min="0" name="vehicle[kilometrage]" value="{{ $dv('kilometrage') }}" class="input"></div>
            <div><label class="label">Numéro de châssis</label><input name="vehicle[numero_chassis]" value="{{ $dv('numero_chassis') }}" class="input"></div>
            <div><label class="label">Numéro de moteur</label><input name="vehicle[numero_moteur]" value="{{ $dv('numero_moteur') }}" class="input"></div>
            <div><label class="label">Fin d'assurance</label><input type="date" name="vehicle[assurance_fin]" value="{{ $dv('assurance_fin') ? \Illuminate\Support\Carbon::parse($dv('assurance_fin'))->format('Y-m-d') : '' }}" class="input"></div>
            <div><label class="label">Fin de visite technique</label><input type="date" name="vehicle[visite_technique_fin]" value="{{ $dv('visite_technique_fin') ? \Illuminate\Support\Carbon::parse($dv('visite_technique_fin'))->format('Y-m-d') : '' }}" class="input"></div>
            <div><label class="label">Prochaine revue</label><input type="date" name="vehicle[prochaine_revue]" value="{{ $dv('prochaine_revue') ? \Illuminate\Support\Carbon::parse($dv('prochaine_revue'))->format('Y-m-d') : '' }}" class="input"></div>
            <div class="sm:col-span-2"><label class="label">Observations</label><textarea name="vehicle[observations]" rows="2" class="input">{{ $dv('observations') }}</textarea></div>
        </div>
    </div>
    @endif

    <div class="flex gap-3">
        <button class="btn-primary">Enregistrer les modifications</button>
        <a href="{{ route('patrimoine.show', $d['id']) }}" class="btn-secondary">Annuler</a>
    </div>
</form>
@endsection
