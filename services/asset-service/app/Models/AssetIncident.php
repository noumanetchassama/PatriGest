<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetIncident extends Model
{
    public const TYPE_DOMMAGE = 'dommage';
    public const TYPE_PERTE = 'perte';
    public const TYPE_ACCIDENT = 'accident';
    public const TYPE_VOL = 'vol';

    public const TYPES = [
        self::TYPE_DOMMAGE, self::TYPE_PERTE, self::TYPE_ACCIDENT, self::TYPE_VOL,
    ];

    public const SEVERITY_MINEURE = 'mineure';
    public const SEVERITY_MAJEURE = 'majeure';
    public const SEVERITY_CRITIQUE = 'critique';

    public const SEVERITIES = [
        self::SEVERITY_MINEURE, self::SEVERITY_MAJEURE, self::SEVERITY_CRITIQUE,
    ];

    public const STATUS_OUVERT = 'ouvert';
    public const STATUS_EN_COURS = 'en_cours';
    public const STATUS_RESOLU = 'resolu';
    public const STATUS_CLOS = 'clos';

    public const STATUSES = [
        self::STATUS_OUVERT, self::STATUS_EN_COURS, self::STATUS_RESOLU, self::STATUS_CLOS,
    ];

    protected $fillable = [
        'asset_id', 'type', 'description', 'occurred_at', 'severity', 'status',
        'estimated_cost', 'resolution_notes', 'resolved_at', 'reported_by',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'resolved_at' => 'datetime',
            'estimated_cost' => 'decimal:2',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
