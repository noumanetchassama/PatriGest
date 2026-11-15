<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetInventory extends Model
{
    public const STATUS_EN_COURS = 'en_cours';
    public const STATUS_CLOS = 'clos';

    public const STATUSES = [self::STATUS_EN_COURS, self::STATUS_CLOS];

    protected $fillable = [
        'name', 'description', 'status', 'started_by', 'closed_by', 'closed_at',
    ];

    protected function casts(): array
    {
        return ['closed_at' => 'datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssetInventoryItem::class);
    }

    public function pendingItemsCount(): int
    {
        return $this->items()->where('status', AssetInventoryItem::STATUS_EN_ATTENTE)->count();
    }
}
