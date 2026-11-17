<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Faire confiance au proxy HTTPS de Render.
        // X-Forwarded-Host n'est PAS fiable chez Render/Cloudflare (envoyé
        // vide) : on garde le vrai Host transmis par nginx. Sans cela,
        // getHost() renvoie vide et les URLs générées deviennent
        // « https:/login » puis « https:/https:/login » (404).
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PREFIX,
        );

        $middleware->alias([
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 419 « Page expirée » (jeton CSRF/session périmé, ex. page
        // login servie depuis le cache navigateur) : on renvoie
        // proprement vers /login avec un message au lieu de la page
        // d'erreur. Les requêtes API conservent la réponse 419.
        $exceptions->respond(function ($response, $exception, Request $request) {
            if ($response->getStatusCode() === 419
                && ! $request->is('api/*')
                && ! $request->expectsJson()) {
                return redirect()->route('login')
                    ->withErrors(['login' => 'Votre session a expiré. Veuillez réessayer.']);
            }

            return $response;
        });
    })->create();

    