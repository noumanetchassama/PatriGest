<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDetail extends Model
{
    protected $table = 'vehicle_details';

    protected $fillable = [
        'asset_id', 'immatriculation', 'marque', 'modele', 'annee',
        'carburant', 'kilometrage', 'numero_chassis', 'numero_moteur',
        'assurance_fin', 'visite_technique_fin', 'prochaine_revue', 'observations',
    ];

    protected function casts(): array
    {
        return [
            'assurance_fin' => 'date',
            'visite_technique_fin' => 'date',
            'prochaine_revue' => 'date',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
