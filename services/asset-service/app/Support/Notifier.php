<?php

namespace App\Support;

use App\Models\Notification;

/**
 * Notifications persistées par utilisateur (ID Auth Service).
 * À appeler APRÈS commit des décisions de workflow.
 */
class Notifier
{
    public static function send(?int $userId, string $type, string $title, ?string $message = null, ?string $link = null): ?Notification
    {
        if (! $userId) {
            return null;
        }

        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ]);
    }
}
