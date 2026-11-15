<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PatriGest')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $user = auth()->user();
    $nav = [
        ['label' => 'Tableau de bord', 'route' => 'dashboard', 'icon' => 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
        ['label' => 'Patrimoine', 'route' => 'patrimoine.index', 'icon' => 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6'],
        ['label' => 'Affectations', 'route' => 'affectations.index', 'icon' => 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75'],
        ['label' => 'Transferts', 'route' => 'transferts.index', 'icon' => 'M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4'],
        ['label' => 'Validations', 'route' => 'validations.index', 'icon' => 'M9 12l2 2 4-4m5 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0z'],
        ['label' => 'Inventaires', 'route' => 'inventaires.index', 'icon' => 'M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2'],
        ['label' => 'Incidents et maintenance', 'route' => 'incidents.index', 'icon' => 'M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z'],
        ['label' => 'Documents', 'route' => 'documents.index', 'icon' => 'M7 21h10a2 2 0 0 0 2-2V9.41a2 2 0 0 0-.59-1.41l-5.41-5.41A2 2 0 0 0 11.59 2H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z'],
        ['label' => 'Rapports', 'route' => 'rapports.index', 'icon' => 'M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z'],
        ['label' => 'Notifications', 'route' => 'notifications.index', 'icon' => 'M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4-5.7V5a2 2 0 1 0-4 0v.3A6 6 0 0 0 6 11v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0v1a3 3 0 0 1-6 0v-1m6 0H9'],
        ['label' => 'Administration', 'route' => 'administration.index', 'icon' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z'],
        ['label' => 'Paramètres', 'route' => 'profil.show', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
    ];
@endphp
<body class="min-h-screen bg-background font-sans text-ink antialiased">
<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-30 flex w-[230px] flex-col bg-navy">
        <div class="flex items-center gap-3 px-5 py-6">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>
                </svg>
            </div>
            <span class="text-lg font-bold text-white">PatriGest</span>
        </div>
        <nav class="flex-1 space-y-0.5 overflow-y-auto px-3">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-[13px] font-medium text-white/80 transition-colors hover:bg-primary {{ request()->routeIs($item['route']) || (isset($item['active']) && request()->routeIs($item['active'])) ? 'bg-primary text-white' : '' }}">
                    <svg class="h-[18px] w-[18px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/>
                    </svg>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        <div class="border-t border-white/10 px-5 py-4 text-[11px] leading-relaxed text-white/50">
            Ministère des Finances et du Budget<br>République togolaise
        </div>
    </aside>

    {{-- Contenu --}}
    <div class="ml-[230px] flex min-h-screen flex-1 flex-col">
        <header class="sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-border bg-white px-6 py-3">
            <div class="relative w-full max-w-md">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"/>
                </svg>
                <input type="search" placeholder="Rechercher un bien, une référence…" class="input pl-9">
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('notifications.index') }}" class="relative rounded-lg p-2 text-muted hover:bg-primary-light">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-4-5.7V5a2 2 0 1 0-4 0v.3A6 6 0 0 0 6 11v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0v1a3 3 0 0 1-6 0v-1m6 0H9"/>
                    </svg>
                    <span class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-danger text-[10px] font-bold text-white">3</span>
                </a>
                <div class="flex items-center gap-3 border-l border-border pl-4">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-light text-sm font-bold text-primary">
                        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                    </div>
                    <div class="leading-tight">
                        <div class="text-sm font-semibold">{{ $user->name }}</div>
                        <div class="text-xs text-muted">{{ $user->roles->first()?->name ?? 'Utilisateur' }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" title="Se déconnecter" class="rounded-lg p-2 text-muted hover:bg-danger/10 hover:text-danger">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-1 px-6 py-6">
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
