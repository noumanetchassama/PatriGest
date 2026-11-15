<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Structure extends Model
{
    use SoftDeletes;

    public const TYPE_ADMINISTRATION = 'administration';
    public const TYPE_DIRECTION = 'direction';
    public const TYPE_SERVICE = 'service';

    public const TYPES = [
        self::TYPE_ADMINISTRATION,
        self::TYPE_DIRECTION,
        self::TYPE_SERVICE,
    ];

    protected $fillable = [
        'parent_id',
        'type',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Structure::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Structure::class, 'parent_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_structure')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
