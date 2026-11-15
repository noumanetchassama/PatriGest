<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RealEstateDetail extends Model
{
    protected $table = 'real_estate_details';

    protected $fillable = [
        'asset_id', 'bien_type', 'surface_terrain', 'surface_batie',
        'nb_pieces', 'annee_construction', 'reference_cadastrale',
        'occupant_type', 'titre_possession', 'observations',
    ];

    protected function casts(): array
    {
        return [
            'surface_terrain' => 'decimal:2',
            'surface_batie' => 'decimal:2',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
