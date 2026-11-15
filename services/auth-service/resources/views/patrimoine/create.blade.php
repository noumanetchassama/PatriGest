@extends('layouts.app')

@section('title', 'Enregistrer un bien — PatriGest')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-navy">Enregistrer un nouveau bien</h1>
    <p class="mt-1 text-sm text-muted">Renseignez les informations du bien en 5 étapes</p>
</div>

@if ($errors->any())
    <div class="mb-4 rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" action="{{ route('patrimoine.store') }}" enctype="multipart/form-data" id="asset-form">
    @csrf

    {{-- Indicateur d'étapes --}}
    <div class="card mb-6">
        <ol class="flex flex-wrap items-center gap-2 text-sm">
            @foreach (['Général', 'Administratif', 'Localisation', 'Documents', 'Vérification'] as $i => $label)
                <li class="flex items-center gap-2">
                    <span class="wizard-dot flex h-7 w-7 items-center justify-center rounded-full border text-xs font-semibold"
                          data-step="{{ $i + 1 }}">{{ $i + 1 }}</span>
                    <span class="wizard-label text-muted">{{ $label }}</span>
                    @if ($i < 4)<span class="mx-1 text-border">›</span>@endif
                </li>
            @endforeach
        </ol>
        <p class="mt-3 text-sm font-medium text-navy" id="wizard-title">Étape 1 sur 5 — Informations générales</p>
    </div>

    {{-- Étape 1 : Général --}}
    <section class="wizard-step card" data-step="1">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Catégorie du bien <span class="text-danger">*</span></label>
                <select name="type" id="asset-type" class="input" required>
                    <option value="">— Choisir —</option>
                    <option value="immobilier" @selected(old('type') === 'immobilier')>Bien immobilier</option>
                    <option value="mobilier" @selected(old('type') === 'mobilier')>Bien mobilier</option>
                    <option value="vehicule" @selected(old('type') === 'vehicule')>Véhicule</option>
                </select>
            </div>
            <div>
                <label class="label">Désignation <span class="text-danger">*</span></label>
                <input name="designation" value="{{ old('designation') }}" placeholder="Ex. : Imprimante HP LaserJet" class="input" required>
            </div>
            <div class="sm:col-span-2">
                <label class="label">Description</label>
                <textarea name="description" rows="2" class="input" placeholder="Caractéristiques, remarques…">{{ old('description') }}</textarea>
            </div>
            <div>
                <label class="label">Date d'acquisition</label>
                <input type="date" name="acquisition_date" value="{{ old('acquisition_date') }}" class="input">
            </div>
            <div>
                <label class="label">Valeur (FCFA)</label>
                <input type="number" name="value" min="0" step="1" value="{{ old('value') }}" class="input" placeholder="Ex. : 650000">
            </div>
            <div>
                <label class="label">Nature de la valeur</label>
                <select name="value_type" class="input">
                    <option value="">—</option>
                    @foreach (['acquisition' => 'Acquisition', 'actuelle' => 'Valeur actuelle', 'estimee' => 'Valeur estimée'] as $v => $l)
                        <option value="{{ $v }}" @selected(old('value_type') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Origine ou provenance</label>
                <input name="origin" value="{{ old('origin') }}" placeholder="Ex. : Acquisition, don, transfert…" class="input">
            </div>
        </div>
    </section>

    {{-- Étape 2 : Administratif --}}
    <section class="wizard-step card hidden" data-step="2">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">État du bien</label>
                <select name="etat" class="input">
                    <option value="">—</option>
                    @foreach (['excellent' => 'Excellent', 'bon' => 'Bon', 'moyen' => 'Moyen', 'mauvais' => 'Mauvais'] as $v => $l)
                        <option value="{{ $v }}" @selected(old('etat') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Service</label>
                <select name="service_id" class="input">
                    <option value="">—</option>
                    @foreach ($services as $s)
                        <option value="{{ $s->id }}" @selected((string) old('service_id') === (string) $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Direction</label>
                <select name="direction_id" class="input">
                    <option value="">—</option>
                    @foreach ($directions as $d)
                        <option value="{{ $d->id }}" @selected((string) old('direction_id') === (string) $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Dépositaire</label>
                <select name="custodian_user_id" class="input">
                    <option value="">—</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected((string) old('custodian_user_id') === (string) $u->id)>{{ $u->name }}{{ $u->username ? ' ('.$u->username.')' : '' }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Champs spécifiques : bien immobilier --}}
            <div class="type-fields sm:col-span-2 hidden" data-type="immobilier">
                <h3 class="mb-3 text-sm font-semibold text-navy">Informations immobilières</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Type de bien</label>
                        <select name="real_estate[bien_type]" class="input">
                            <option value="">—</option>
                            @foreach (['terrain' => 'Terrain', 'batiment' => 'Bâtiment', 'bureau' => 'Bureau', 'logement' => 'Logement', 'infrastructure' => 'Infrastructure'] as $v => $l)
                                <option value="{{ $v }}" @selected(old('real_estate.bien_type') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Référence cadastrale</label>
                        <input name="real_estate[reference_cadastrale]" value="{{ old('real_estate.reference_cadastrale') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Superficie du terrain (m²)</label>
                        <input type="number" name="real_estate[surface_terrain]" min="0" step="0.01" value="{{ old('real_estate.surface_terrain') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Superficie bâtie (m²)</label>
                        <input type="number" name="real_estate[surface_batie]" min="0" step="0.01" value="{{ old('real_estate.surface_batie') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Nombre de pièces</label>
                        <input type="number" name="real_estate[nb_pieces]" min="0" value="{{ old('real_estate.nb_pieces') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Année de construction</label>
                        <input type="number" name="real_estate[annee_construction]" min="1800" max="2100" value="{{ old('real_estate.annee_construction') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Statut foncier / titre de possession</label>
                        <input name="real_estate[titre_possession]" value="{{ old('real_estate.titre_possession') }}" placeholder="Ex. : Titre foncier" class="input">
                    </div>
                    <div>
                        <label class="label">Type d'occupant</label>
                        <input name="real_estate[occupant_type]" value="{{ old('real_estate.occupant_type') }}" class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Observations</label>
                        <textarea name="real_estate[observations]" rows="2" class="input">{{ old('real_estate.observations') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Champs spécifiques : bien mobilier --}}
            <div class="type-fields sm:col-span-2 hidden" data-type="mobilier">
                <h3 class="mb-3 text-sm font-semibold text-navy">Informations du mobilier</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Marque</label>
                        <input name="movable[marque]" value="{{ old('movable.marque') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Modèle</label>
                        <input name="movable[modele]" value="{{ old('movable.modele') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Numéro de série</label>
                        <input name="movable[numero_serie]" value="{{ old('movable.numero_serie') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Fournisseur</label>
                        <input name="movable[fournisseur]" value="{{ old('movable.fournisseur') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Fin de garantie</label>
                        <input type="date" name="movable[garantie_fin]" value="{{ old('movable.garantie_fin') }}" class="input">
                    </div>
                    <div>
                        <label class="label">État physique</label>
                        <input name="movable[etat_physique]" value="{{ old('movable.etat_physique') }}" class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Observations</label>
                        <textarea name="movable[observations]" rows="2" class="input">{{ old('movable.observations') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Champs spécifiques : véhicule --}}
            <div class="type-fields sm:col-span-2 hidden" data-type="vehicule">
                <h3 class="mb-3 text-sm font-semibold text-navy">Informations du véhicule</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label">Immatriculation</label>
                        <input name="vehicle[immatriculation]" value="{{ old('vehicle.immatriculation') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Marque</label>
                        <input name="vehicle[marque]" value="{{ old('vehicle.marque') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Modèle</label>
                        <input name="vehicle[modele]" value="{{ old('vehicle.modele') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Année</label>
                        <input type="number" name="vehicle[annee]" min="1950" max="2100" value="{{ old('vehicle.annee') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Carburant</label>
                        <select name="vehicle[carburant]" class="input">
                            <option value="">—</option>
                            @foreach (['essence' => 'Essence', 'diesel' => 'Diesel', 'electrique' => 'Électrique', 'hybride' => 'Hybride'] as $v => $l)
                                <option value="{{ $v }}" @selected(old('vehicle.carburant') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Kilométrage</label>
                        <input type="number" name="vehicle[kilometrage]" min="0" value="{{ old('vehicle.kilometrage') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Numéro de châssis</label>
                        <input name="vehicle[numero_chassis]" value="{{ old('vehicle.numero_chassis') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Numéro de moteur</label>
                        <input name="vehicle[numero_moteur]" value="{{ old('vehicle.numero_moteur') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Fin d'assurance</label>
                        <input type="date" name="vehicle[assurance_fin]" value="{{ old('vehicle.assurance_fin') }}" class="input">
                    </div>
                    <div>
                        <label class="label">Fin de visite technique</label>
                        <input type="date" name="vehicle[visite_technique_fin]" value="{{ old('vehicle.visite_technique_fin') }}" class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Observations</label>
                        <textarea name="vehicle[observations]" rows="2" class="input">{{ old('vehicle.observations') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Étape 3 : Localisation --}}
    <section class="wizard-step card hidden" data-step="3">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Région</label>
                <input name="location[region]" value="{{ old('location.region') }}" placeholder="Ex. : Maritime" class="input">
            </div>
            <div>
                <label class="label">Préfecture</label>
                <input name="location[prefecture]" value="{{ old('location.prefecture') }}" class="input">
            </div>
            <div>
                <label class="label">Commune</label>
                <input name="location[commune]" value="{{ old('location.commune') }}" placeholder="Ex. : Lomé" class="input">
            </div>
            <div>
                <label class="label">Adresse</label>
                <input name="location[adresse]" value="{{ old('location.adresse') }}" class="input">
            </div>
            <div class="sm:col-span-2">
                <label class="label">Site / bâtiment</label>
                <input name="location[site]" value="{{ old('location.site') }}" placeholder="Ex. : Siège MFB, 3e étage" class="input">
            </div>
            <div>
                <label class="label">Latitude</label>
                <input type="number" name="location[latitude]" step="any" min="-90" max="90" value="{{ old('location.latitude') }}" class="input">
            </div>
            <div>
                <label class="label">Longitude</label>
                <input type="number" name="location[longitude]" step="any" min="-180" max="180" value="{{ old('location.longitude') }}" class="input">
            </div>
        </div>
        <p class="mt-3 text-xs text-muted">Tous les champs sont optionnels — la localisation pourra être complétée ou historisée plus tard.</p>
    </section>

    {{-- Étape 4 : Documents --}}
    <section class="wizard-step card hidden" data-step="4">
        <div id="doc-rows" class="space-y-4"></div>
        <button type="button" id="add-doc" class="btn-secondary text-sm">+ Ajouter un document</button>
        <p class="mt-3 text-xs text-muted">Formats acceptés : PDF, images, Office — 20 Mo max par fichier.</p>
    </section>

    {{-- Étape 5 : Vérification --}}
    <section class="wizard-step card hidden" data-step="5">
        <div id="recap" class="space-y-4 text-sm"></div>
        <div class="mt-6 rounded-lg border border-border bg-background p-4">
            <label class="label">Enregistrement</label>
            <label class="flex items-center gap-2 text-sm">
                <input type="radio" name="action" value="draft" checked class="accent-primary">
                Enregistrer comme brouillon (modifiable ensuite)
            </label>
            <label class="mt-2 flex items-center gap-2 text-sm">
                <input type="radio" name="action" value="submit" class="accent-primary">
                Soumettre pour validation (le bien passera « en validation »)
            </label>
        </div>
    </section>

    {{-- Navigation --}}
    <div class="mt-6 flex items-center justify-between">
        <button type="button" id="prev-btn" class="btn-secondary hidden">← Précédent</button>
        <div class="ml-auto flex gap-3">
            <button type="button" id="next-btn" class="btn-primary">Suivant →</button>
            <button type="submit" id="submit-btn" class="btn-primary hidden">Enregistrer le bien</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const steps = document.querySelectorAll('.wizard-step');
    const titles = [
        'Étape 1 sur 5 — Informations générales',
        'Étape 2 sur 5 — Administratif',
        'Étape 3 sur 5 — Localisation',
        'Étape 4 sur 5 — Documents',
        'Étape 5 sur 5 — Vérification',
    ];
    let current = 1;
    const maxDocs = 5;

    const typeSelect = document.getElementById('asset-type');
    const typeLabels = { immobilier: 'Bien immobilier', mobilier: 'Bien mobilier', vehicule: 'Véhicule' };

    function syncTypeFields() {
        const t = typeSelect.value;
        document.querySelectorAll('.type-fields').forEach(el => {
            el.classList.toggle('hidden', el.dataset.type !== t);
        });
    }
    typeSelect.addEventListener('change', syncTypeFields);
    syncTypeFields();

    function showStep(n) {
        current = n;
        steps.forEach(el => el.classList.toggle('hidden', +el.dataset.step !== n));
        document.querySelectorAll('.wizard-dot').forEach(dot => {
            const s = +dot.dataset.step;
            dot.classList.toggle('bg-primary', s === n);
            dot.classList.toggle('text-white', s === n);
            dot.classList.toggle('border-primary', s === n);
            dot.classList.toggle('bg-success/15', s < n);
            dot.classList.toggle('text-success', s < n);
            dot.classList.toggle('border-success/40', s < n);
            dot.classList.toggle('text-muted', s > n);
            dot.classList.toggle('border-border', s !== n);
        });
        document.querySelectorAll('.wizard-label').forEach((el, i) => {
            el.classList.toggle('text-navy', i + 1 === n);
            el.classList.toggle('font-medium', i + 1 === n);
        });
        document.getElementById('wizard-title').textContent = titles[n - 1];
        document.getElementById('prev-btn').classList.toggle('hidden', n === 1);
        document.getElementById('next-btn').classList.toggle('hidden', n === 5);
        document.getElementById('submit-btn').classList.toggle('hidden', n !== 5);
        if (n === 5) buildRecap();
    }

    function validStep1() {
        return typeSelect.value && document.querySelector('[name=designation]').value.trim();
    }

    document.getElementById('next-btn').addEventListener('click', () => {
        if (current === 1 && !validStep1()) {
            typeSelect.reportValidity();
            document.querySelector('[name=designation]').reportValidity();
            return;
        }
        showStep(Math.min(current + 1, 5));
    });
    document.getElementById('prev-btn').addEventListener('click', () => showStep(Math.max(current - 1, 1)));
    showStep(1);

    // ---- Documents dynamiques ----
    const docRows = document.getElementById('doc-rows');
    let docIndex = 0;
    const docTypes = ['facture', 'contrat', 'garantie', 'carte_grise', 'autre'];
    const docTypeLabels = { facture: 'Facture', contrat: 'Contrat', garantie: 'Garantie', carte_grise: 'Carte grise', autre: 'Autre' };

    function addDocRow() {
        if (docIndex >= maxDocs) return;
        const i = docIndex++;
        const row = document.createElement('div');
        row.className = 'grid gap-3 rounded-lg border border-border p-3 sm:grid-cols-[1fr_160px_1fr_auto]';
        row.innerHTML = `
            <input type="file" name="documents[${i}][file]" class="input text-sm">
            <select name="documents[${i}][type]" class="input text-sm">
                ${docTypes.map(t => `<option value="${t}">${docTypeLabels[t]}</option>`).join('')}
            </select>
            <input name="documents[${i}][name]" placeholder="Nom du document (optionnel)" class="input text-sm">
            <button type="button" class="btn-secondary text-xs" onclick="this.closest('div').remove()">Retirer</button>`;
        docRows.appendChild(row);
    }
    document.getElementById('add-doc').addEventListener('click', addDocRow);
    addDocRow();

    // ---- Récapitulatif étape 5 ----
    function val(name) {
        const el = document.querySelector(`[name="${name}"]`);
        return el ? el.value.trim() : '';
    }
    function section(title, entries) {
        const rows = entries.filter(([, v]) => v).map(([k, v]) =>
            `<div class="flex justify-between gap-4 border-b border-border/50 py-1.5"><span class="text-muted">${k}</span><span class="text-right font-medium">${v}</span></div>`
        ).join('');
        return rows ? `<div><h4 class="mb-1 font-semibold text-navy">${title}</h4><div>${rows}</div></div>` : '';
    }

    function buildRecap() {
        const t = typeSelect.value;
        const typeKey = { immobilier: 'real_estate', mobilier: 'movable', vehicule: 'vehicle' }[t];
        const fieldLabels = {
            'real_estate.bien_type': 'Type de bien', 'real_estate.reference_cadastrale': 'Réf. cadastrale',
            'real_estate.surface_terrain': 'Superficie terrain', 'real_estate.surface_batie': 'Superficie bâtie',
            'real_estate.nb_pieces': 'Pièces', 'real_estate.annee_construction': 'Année construction',
            'real_estate.titre_possession': 'Titre de possession', 'real_estate.occupant_type': 'Occupant',
            'movable.marque': 'Marque', 'movable.modele': 'Modèle', 'movable.numero_serie': 'N° de série',
            'movable.fournisseur': 'Fournisseur', 'movable.garantie_fin': 'Fin de garantie',
            'vehicle.immatriculation': 'Immatriculation', 'vehicle.marque': 'Marque', 'vehicle.modele': 'Modèle',
            'vehicle.annee': 'Année', 'vehicle.carburant': 'Carburant', 'vehicle.kilometrage': 'Kilométrage',
        };
        const typeFields = [];
        if (typeKey) {
            Object.keys(fieldLabels).filter(k => k.startsWith(typeKey + '.')).forEach(k => {
                const v = val(k);
                if (v) typeFields.push([fieldLabels[k], v]);
            });
        }
        const docs = [...docRows.querySelectorAll('input[type=file]')].filter(f => f.files.length).length;

        document.getElementById('recap').innerHTML =
            section('Général', [
                ['Catégorie', typeLabels[t] || ''],
                ['Désignation', val('designation')],
                ['Date d\'acquisition', val('acquisition_date')],
                ['Valeur', val('value') ? Number(val('value')).toLocaleString('fr-FR') + ' FCFA' : ''],
                ['Nature de la valeur', val('value_type')],
                ['Origine', val('origin')],
            ]) +
            section('Administratif', [
                ['État', val('etat')],
                ['Direction', document.querySelector('[name=direction_id] option:checked')?.textContent || ''],
                ['Service', document.querySelector('[name=service_id] option:checked')?.textContent || ''],
                ['Dépositaire', document.querySelector('[name=custodian_user_id] option:checked')?.textContent || ''],
                ...typeFields,
            ]) +
            section('Localisation', [
                ['Région', val('location[region]')],
                ['Préfecture', val('location[prefecture]')],
                ['Commune', val('location[commune]')],
                ['Adresse', val('location[adresse]')],
                ['Site', val('location[site]')],
            ]) +
            section('Documents', docs ? [[docs + ' fichier(s) joint(s)', '']].map(([k]) => [k, '']) : []);
    }
})();
</script>
@endpush
