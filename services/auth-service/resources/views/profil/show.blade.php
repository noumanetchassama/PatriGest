@extends('layouts.app')

@section('title', 'Profil et paramètres — PatriGest')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-navy">Profil et paramètres</h1>
    <p class="mt-1 text-sm text-muted">Vos informations et préférences</p>
</div>

@if (session('success'))
    <div class="mb-4 rounded-lg border border-success/40 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger">{{ $errors->first() }}</div>
@endif

<div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
    <div class="card">
        <h2 class="mb-4 text-base font-bold text-navy">Informations</h2>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between border-b border-border/60 pb-2"><dt class="text-muted">Nom</dt><dd class="font-medium">{{ $user->name }}</dd></div>
            <div class="flex justify-between border-b border-border/60 pb-2"><dt class="text-muted">Identifiant</dt><dd class="font-medium">{{ $user->username ?? '—' }}</dd></div>
            <div class="flex justify-between border-b border-border/60 pb-2"><dt class="text-muted">E-mail</dt><dd class="font-medium">{{ $user->email }}</dd></div>
            <div class="flex justify-between border-b border-border/60 pb-2"><dt class="text-muted">Rôle</dt><dd class="font-medium">{{ $user->roles->first()?->name ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted">Dernière connexion</dt><dd class="font-medium">{{ $user->last_login_at ? \Illuminate\Support\Carbon::parse($user->last_login_at)->format('d/m/Y') : '—' }}</dd></div>
        </dl>
    </div>

    <div class="card">
        <h2 class="mb-4 text-base font-bold text-navy">Changer le mot de passe</h2>
        <form method="POST" action="{{ route('profil.password') }}" class="space-y-3">
            @csrf
            <div>
                <label class="label">Mot de passe actuel</label>
                <input type="password" name="current_password" required class="input" autocomplete="current-password">
            </div>
            <div>
                <label class="label">Nouveau mot de passe</label>
                <input type="password" name="password" required class="input" autocomplete="new-password">
            </div>
            <div>
                <label class="label">Confirmer le nouveau mot de passe</label>
                <input type="password" name="password_confirmation" required class="input" autocomplete="new-password">
            </div>
            <button class="btn-primary">Mettre à jour</button>
        </form>
    </div>
</div>
@endsection
