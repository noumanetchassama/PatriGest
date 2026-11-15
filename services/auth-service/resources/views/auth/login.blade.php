@extends('layouts.guest')

@section('title', 'Connexion — PatriGest')

@section('content')
<div class="flex min-h-screen">
    {{-- Panneau de marque (maquette 01) --}}
    <div class="hidden w-1/2 flex-col justify-between bg-navy px-16 py-10 text-white lg:flex">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"/>
                </svg>
            </div>
            <span class="text-2xl font-bold">PatriGest</span>
        </div>
        <div>
            <h1 class="text-4xl font-bold leading-tight">Gestion du patrimoine public</h1>
            <p class="mt-4 max-w-md text-base text-primary-light">
                Recenser, affecter, suivre et protéger les biens<br>
                immobiliers, mobiliers et les véhicules.
            </p>
        </div>
        <p class="text-sm text-primary-light">
            Ministère des Finances et du Budget — République togolaise
        </p>
    </div>

    {{-- Formulaire --}}
    <div class="flex flex-1 items-center justify-center bg-background px-6 py-10">
        <div class="w-full max-w-md">
            <div class="rounded-xl bg-white p-10 shadow-sm">
                <h2 class="text-2xl font-bold text-navy">Connexion</h2>
                <p class="mt-1 text-sm text-muted">Connectez-vous pour accéder à votre espace de gestion.</p>

                @if ($errors->any())
                    <div class="mt-5 rounded-lg border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <label for="login" class="mb-1 block text-[13px] font-semibold text-ink">Adresse e-mail ou identifiant</label>
                        <input
                            type="text"
                            id="login"
                            name="login"
                            value="{{ old('login') }}"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="a.koffi@mfb.exemple.tg"
                            class="input"
                        >
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-[13px] font-semibold text-ink">Mot de passe</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="input"
                        >
                    </div>

                    <button type="submit" class="btn-primary w-full py-2.5!">
                        Se connecter
                    </button>
                </form>

                <p class="mt-6 text-center text-xs text-muted">Accès réservé aux agents habilités.</p>
                <p class="mt-1 text-center text-xs text-muted">Vos actions sont enregistrées et tracées.</p>
            </div>
        </div>
    </div>
</div>
@endsection
