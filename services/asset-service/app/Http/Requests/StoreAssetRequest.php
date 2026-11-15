<?php

namespace App\Http\Requests;

use App\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la permission est vérifiée par le middleware auth.patri:create-assets
    }

    public function rules(): array
    {
        $type = $this->input('type');

        return [
            'type' => ['required', 'string', Rule::in(Asset::TYPES)],
            'designation' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:100'],
            'acquisition_date' => ['nullable', 'date'],
            'origin' => ['nullable', 'string', 'max:255'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'value_type' => ['nullable', 'string', Rule::in(['acquisition', 'actuelle', 'estimee'])],
            'status' => ['nullable', 'string', Rule::in(Asset::MANUAL_STATUSES)],
            'etat' => ['nullable', 'string', Rule::in(['excellent', 'bon', 'moyen', 'mauvais'])],
            'direction_id' => ['nullable', 'integer', 'min:1'],
            'service_id' => ['nullable', 'integer', 'min:1'],
            'custodian_user_id' => ['nullable', 'integer', 'min:1'],

            // Détails spécifiques par type (table séparée, jamais mélangés)
            'real_estate' => [$type === Asset::TYPE_IMMOBILIER ? 'present' : 'prohibited', 'array'],
            'real_estate.bien_type' => ['nullable', 'string', Rule::in(['terrain', 'batiment', 'bureau', 'logement', 'infrastructure'])],
            'real_estate.surface_terrain' => ['nullable', 'numeric', 'min:0'],
            'real_estate.surface_batie' => ['nullable', 'numeric', 'min:0'],
            'real_estate.nb_pieces' => ['nullable', 'integer', 'min:0'],
            'real_estate.annee_construction' => ['nullable', 'integer', 'min:1800', 'max:2100'],
            'real_estate.reference_cadastrale' => ['nullable', 'string', 'max:100'],
            'real_estate.occupant_type' => ['nullable', 'string', 'max:100'],
            'real_estate.titre_possession' => ['nullable', 'string', 'max:255'],
            'real_estate.observations' => ['nullable', 'string', 'max:5000'],

            'movable' => [$type === Asset::TYPE_MOBILIER ? 'present' : 'prohibited', 'array'],
            'movable.marque' => ['nullable', 'string', 'max:100'],
            'movable.modele' => ['nullable', 'string', 'max:100'],
            'movable.numero_serie' => ['nullable', 'string', 'max:100'],
            'movable.fournisseur' => ['nullable', 'string', 'max:255'],
            'movable.garantie_fin' => ['nullable', 'date'],
            'movable.etat_physique' => ['nullable', 'string', 'max:100'],
            'movable.observations' => ['nullable', 'string', 'max:5000'],

            'vehicle' => [$type === Asset::TYPE_VEHICULE ? 'present' : 'prohibited', 'array'],
            'vehicle.immatriculation' => ['nullable', 'string', 'max:20'],
            'vehicle.marque' => ['nullable', 'string', 'max:100'],
            'vehicle.modele' => ['nullable', 'string', 'max:100'],
            'vehicle.annee' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'vehicle.carburant' => ['nullable', 'string', Rule::in(['essence', 'diesel', 'electrique', 'hybride'])],
            'vehicle.kilometrage' => ['nullable', 'integer', 'min:0'],
            'vehicle.numero_chassis' => ['nullable', 'string', 'max:100'],
            'vehicle.numero_moteur' => ['nullable', 'string', 'max:100'],
            'vehicle.assurance_fin' => ['nullable', 'date'],
            'vehicle.visite_technique_fin' => ['nullable', 'date'],
            'vehicle.prochaine_revue' => ['nullable', 'date'],
            'vehicle.observations' => ['nullable', 'string', 'max:5000'],

            // Localisation initiale (optionnelle à la création)
            'location' => ['nullable', 'array'],
            'location.region' => ['nullable', 'string', 'max:100'],
            'location.prefecture' => ['nullable', 'string', 'max:100'],
            'location.commune' => ['nullable', 'string', 'max:100'],
            'location.adresse' => ['nullable', 'string', 'max:255'],
            'location.site' => ['nullable', 'string', 'max:255'],
            'location.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'location.longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
