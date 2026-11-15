<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetLocation extends Model
{
    protected $fillable = [
        'asset_id', 'region', 'prefecture', 'commune', 'adresse', 'site',
        'latitude', 'longitude', 'effective_from', 'effective_to',
        'is_current', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'is_current' => 'boolean',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
