<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDocument extends Model
{
    public const TYPE_FACTURE = 'facture';
    public const TYPE_CONTRAT = 'contrat';
    public const TYPE_GARANTIE = 'garantie';
    public const TYPE_CARTE_GRISE = 'carte_grise';
    public const TYPE_AUTRE = 'autre';

    public const TYPES = [
        self::TYPE_FACTURE, self::TYPE_CONTRAT, self::TYPE_GARANTIE,
        self::TYPE_CARTE_GRISE, self::TYPE_AUTRE,
    ];

    protected $fillable = [
        'asset_id', 'type', 'name', 'file_path', 'mime_type', 'size',
        'description', 'uploaded_by',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
