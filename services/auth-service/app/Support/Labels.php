<?php

namespace App\Support;

use App\Models\Structure;
use App\Models\User;

/**
 * Résout les IDs d'utilisateurs/structures (stockés dans l'Asset Service)
 * en noms lisibles via la base locale de l'Auth Service.
 * Cache statique par requête pour éviter les N+1.
 */
class Labels
{
    private static array $users = [];

    private static array $structures = [];

    public static function user(?int $id): string
    {
        if (! $id) {
            return '—';
        }

        if (! array_key_exists($id, self::$users)) {
            self::$users[$id] = User::find($id)?->name ?? 'Utilisateur #'.$id;
        }

        return self::$users[$id];
    }

    public static function structure(?int $id): string
    {
        if (! $id) {
            return '—';
        }

        if (! array_key_exists($id, self::$structures)) {
            $structure = Structure::find($id);
            self::$structures[$id] = $structure
                ? $structure->name.' ('.$structure->type.')'
                : 'Structure #'.$id;
        }

        return self::$structures[$id];
    }
}
