<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetInventoryItem extends Model
{
    public const STATUS_EN_ATTENTE = 'en_attente';
    public const STATUS_TROUVE = 'trouve';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_NON_CONCERNE = 'non_concerne';

    public const STATUSES = [
        self::STATUS_EN_ATTENTE, self::STATUS_TROUVE,
        self::STATUS_ABSENT, self::STATUS_NON_CONCERNE,
    ];

    protected $fillable = [
        'asset_inventory_id', 'asset_id', 'status', 'notes',
        'checked_by', 'checked_at',
    ];

    protected function casts(): array
    {
        return ['checked_at' => 'datetime'];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(AssetInventory::class, 'asset_inventory_id');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
