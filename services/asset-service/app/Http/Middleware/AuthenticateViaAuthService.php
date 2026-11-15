<?php

namespace App\Http\Middleware;

use App\Support\AuthServiceClient;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authentifie la requête auprès de l'Auth Service via le token Bearer.
 * L'utilisateur PatriGest (id, rôles, permissions) est exposé via
 * $request->attributes->get('patri_user').
 */
class AuthenticateViaAuthService
{
    public function __construct(private AuthServiceClient $auth) {}

    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $user = $this->auth->verify($token);

        if (! $user) {
            return response()->json(['message' => 'Token invalide ou expiré.'], 401);
        }

        $request->attributes->set('patri_user', $user);

        if ($permission && ! self::userHasPermission($user, $permission)) {
            return response()->json(['message' => 'Permission insuffisante.'], 403);
        }

        return $next($request);
    }

    public static function user(Request $request): ?array
    {
        return $request->attributes->get('patri_user');
    }

    public static function userId(Request $request): ?int
    {
        $user = self::user($request);

        return $user['id'] ?? null;
    }

    public static function hasPermission(Request $request, string $permission): bool
    {
        return self::userHasPermission(self::user($request) ?? [], $permission);
    }

    public static function userHasPermission(array $user, string $permission): bool
    {
        $permissions = $user['permissions'] ?? [];

        return in_array($permission, $permissions, true)
            || in_array('*', $permissions, true);
    }
}
