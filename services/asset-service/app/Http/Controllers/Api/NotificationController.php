<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateViaAuthService;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = AuthenticateViaAuthService::userId($request);

        $notifications = Notification::query()
            ->where('user_id', $userId)
            ->when($request->boolean('unread'), fn ($q) => $q->whereNull('read_at'))
            ->latest()
            ->paginate(min((int) $request->input('per_page', 15), 100));

        return NotificationResource::collection($notifications);
    }

    /** Nombre de notifications non lues (badge de la cloche). */
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = AuthenticateViaAuthService::userId($request);

        return response()->json([
            'unread' => Notification::where('user_id', $userId)->whereNull('read_at')->count(),
        ]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        $userId = AuthenticateViaAuthService::userId($request);

        abort_unless($notification->user_id === $userId, 403);

        $notification->update(['read_at' => now()]);

        return response()->json(['data' => ['id' => $notification->id, 'read_at' => $notification->read_at]]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $userId = AuthenticateViaAuthService::userId($request);

        Notification::where('user_id', $userId)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['data' => ['marked' => true]]);
    }
}
