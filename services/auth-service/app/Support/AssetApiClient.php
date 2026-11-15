<?php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client HTTP vers l'Asset Service, authentifié avec le token
 * Sanctum de l'utilisateur stocké en session (`asset_api_token`).
 */
class AssetApiClient
{
    public function __construct(private ?string $token = null) {}

    public static function forSession(\Illuminate\Http\Request $request): self
    {
        $token = $request->session()->get('asset_api_token');

        return new self(is_string($token) ? $token : null);
    }

    public function get(string $path, array $query = []): ?Response
    {
        return $this->request('get', $path, $query);
    }

    public function post(string $path, array $data = []): ?Response
    {
        return $this->request('post', $path, $data);
    }

    public function put(string $path, array $data = []): ?Response
    {
        return $this->request('put', $path, $data);
    }

    public function delete(string $path): ?Response
    {
        return $this->request('delete', $path);
    }

    /** Upload multipart (documents joints). Retourne la Response ou null. */
    public function upload(string $path, \Illuminate\Http\UploadedFile $file, array $fields = []): ?Response
    {
        if (! $this->token) {
            return null;
        }

        try {
            return Http::timeout(100)
                ->withToken($this->token)
                ->acceptJson()
                ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                ->post(rtrim(config('services.asset.url'), '/').$path, $fields);
        } catch (\Throwable $e) {
            Log::warning('Asset API upload failed.', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function request(string $method, string $path, array $payload = []): ?Response
    {
        if (! $this->token) {
            return null;
        }

        try {
            $pending = Http::timeout(100)
                ->withToken($this->token)
                ->acceptJson();

            $url = rtrim(config('services.asset.url'), '/').$path;

            $response = match ($method) {
                'get' => $pending->get($url, $payload),
                'post' => $pending->post($url, $payload),
                'put' => $pending->put($url, $payload),
                'delete' => $pending->delete($url),
                default => null,
            };

            return $response;
        } catch (\Throwable $e) {
            Log::warning('Asset API request failed.', [
                'path' => $path,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /** Extrait la collection `data` paginée d'une réponse API. */
    public static function collection(Response $response): array
    {
        return [
            'items' => $response->json('data') ?? [],
            'meta' => $response->json('meta') ?? [],
        ];
    }

    /**
     * Exécute plusieurs GET en parallèle.
     * Retourne ['cle' => Response|null].
     */
    public function poolGet(array $requests): array
    {
        if (! $this->token) {
            return array_fill_keys(array_keys($requests), null);
        }

        $base = rtrim(config('services.asset.url'), '/');

        try {
            $responses = Http::pool(fn ($pool) => collect($requests)
                ->map(fn ($query, $key) => $pool
                    ->as($key)
                    ->timeout(100)
                    ->withToken($this->token)
                    ->acceptJson()
                    ->get($base.$key, $query ?? []))
                ->all());

            // Normalise : les échecs réseau renvoient des exceptions, pas des Response.
            return collect($responses)
                ->map(fn ($r) => $r instanceof Response ? $r : null)
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Asset API pool request failed.', ['error' => $e->getMessage()]);

            return array_fill_keys(array_keys($requests), null);
        }
    }
}
