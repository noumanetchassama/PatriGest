<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client vers l'Auth Service pour vérifier un token Bearer
 * et récupérer l'utilisateur + ses permissions.
 *
 * Les services tournent en php-fpm derrière nginx : les appels
 * internes passent par http://nginx. Seuls les succès sont
 * mis en cache (60 s) — les échecs ne le sont jamais.
 */
class AuthServiceClient
{
    public function verify(string $token): ?array
    {
        $cacheKey = 'patri_auth_'.md5($token);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $response = Http::timeout(100)
            ->withToken($token)
            ->acceptJson()
            ->get(rtrim(config('services.auth.url'), '/').'/api/v1/auth/me');

        if ($response->serverError()) {
            Log::warning('Auth service returned an error while verifying an asset API token.', [
                'status' => $response->status(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json('data');

        if (! is_array($data)) {
            return null;
        }

        Cache::put($cacheKey, $data, 300);

        return $data;
    }

    public function forget(string $token): void
    {
        Cache::forget('patri_auth_'.md5($token));
    }
}
