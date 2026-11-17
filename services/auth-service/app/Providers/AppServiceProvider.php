<?php

namespace App\Providers;

use App\Support\AssetApiClient;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Derrière un proxy HTTPS (Render), les URLs absolues doivent
        // utiliser APP_URL pour éviter les hôtes vides détectés.
        $appUrl = (string) config('app.url');
        if (str_starts_with($appUrl, 'https://')) {
            URL::forceRootUrl(rtrim($appUrl, '/'));
            URL::forceScheme('https');
        }

        // Badge de notifications non lues dans la topbar (layout authentifié).
        View::composer('layouts.app', function ($view) {
            $request = request();
            $unread = 0;

            if ($request->user() && $request->session()->has('asset_api_token')) {
                $res = AssetApiClient::forSession($request)->get('/api/v1/notifications/unread-count');
                $unread = (int) ($res?->json('unread') ?? 0);
            }

            $view->with('unreadNotifications', $unread);
        });
    }
}
