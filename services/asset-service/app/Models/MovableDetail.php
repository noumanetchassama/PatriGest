<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovableDetail extends Model
{
    protected $table = 'movable_details';

    protected $fillable = [
        'asset_id', 'marque', 'modele', 'numero_serie', 'fournisseur',
        'garantie_fin', 'etat_physique', 'observations',
    ];

    protected function casts(): array
    {
        return ['garantie_fin' => 'date'];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
