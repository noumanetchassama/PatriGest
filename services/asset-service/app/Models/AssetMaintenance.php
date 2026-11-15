<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenance extends Model
{
    public const TYPE_PREVENTIVE = 'preventive';
    public const TYPE_CORRECTIVE = 'corrective';

    public const TYPES = [self::TYPE_PREVENTIVE, self::TYPE_CORRECTIVE];

    public const STATUS_PLANIFIEE = 'planifiee';
    public const STATUS_EN_COURS = 'en_cours';
    public const STATUS_TERMINEE = 'terminee';
    public const STATUS_ANNULEE = 'annulee';

    public const STATUSES = [
        self::STATUS_PLANIFIEE,
        self::STATUS_EN_COURS,
        self::STATUS_TERMINEE,
        self::STATUS_ANNULEE,
    ];

    protected $fillable = [
        'asset_id', 'type', 'description', 'scheduled_date', 'realized_date',
        'cost', 'status', 'provider', 'notes', 'previous_asset_status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'realized_date' => 'date',
            'cost' => 'decimal:2',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
