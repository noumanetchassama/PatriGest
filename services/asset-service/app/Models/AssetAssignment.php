<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAssignment extends Model
{
    public const STATUS_EN_ATTENTE = 'en_attente';
    public const STATUS_APPROUVEE = 'approuvee';
    public const STATUS_REJETEE = 'rejetee';
    public const STATUS_ANNULEE = 'annulee';

    public const STATUSES = [
        self::STATUS_EN_ATTENTE,
        self::STATUS_APPROUVEE,
        self::STATUS_REJETEE,
        self::STATUS_ANNULEE,
    ];

    protected $fillable = [
        'asset_id',
        'custodian_user_id',
        'requested_by',
        'reviewed_by',
        'status',
        'motif',
        'decision_motif',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_EN_ATTENTE;
    }
}
