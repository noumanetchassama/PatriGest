<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferenceCounter extends Model
{
    protected $fillable = ['type', 'last_number'];

    protected function casts(): array
    {
        return ['last_number' => 'integer'];
    }

    /**
     * Incrémente le compteur de façon atomique (verrou de ligne).
     * À appeler dans une transaction.
     */
    public static function next(string $type): int
    {
        $counter = static::where('type', $type)->lockForUpdate()->first();

        if (! $counter) {
            $counter = static::create(['type' => $type, 'last_number' => 0]);
            $counter = static::where('type', $type)->lockForUpdate()->first();
        }

        $counter->increment('last_number');

        return $counter->last_number;
    }
}
