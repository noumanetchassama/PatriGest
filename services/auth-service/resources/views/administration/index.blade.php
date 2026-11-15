@extends('layouts.app')

@section('title', 'Administration — PatriGest')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-navy">Administration</h1>
    <p class="mt-1 text-sm text-muted">Utilisateurs, rôles et structures</p>
</div>

<div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
    <div class="card overflow-x-auto p-0">
        <div class="border-b border-border px-5 py-3"><h2 class="text-base font-bold text-navy">Utilisateurs</h2></div>
        <table class="w-full">
            <thead>
                <tr class="border-b border-border">
                    <th class="th">Nom</th>
                    <th class="th">Rôle</th>
                    <th class="th">Statut</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $u)
                    <tr class="border-b border-border/60 last:border-0">
                        <td class="td font-medium">{{ $u->name }}</td>
                        <td class="td text-muted">{{ $u->roles->first()?->name ?? '—' }}</td>
                        <td class="td"><x-status-badge :status="$u->is_active ? 'actif' : 'hors_service'" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-5 py-3">{{ $users->links() }}</div>
    </div>

    <div class="space-y-4">
        <div class="card">
            <h2 class="mb-3 text-base font-bold text-navy">Rôles</h2>
            <ul class="space-y-2 text-sm">
                @foreach ($roles as $role)
                    <li class="flex items-center justify-between border-b border-border/60 pb-2 last:border-0">
                        <span class="font-medium">{{ $role->name }}</span>
                        <span class="text-muted">{{ $role->users_count }} utilisateur(s)</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="card">
            <h2 class="mb-3 text-base font-bold text-navy">Structures</h2>
            <ul class="space-y-2 text-sm">
                @foreach ($structures as $s)
                    <li class="flex items-center justify-between border-b border-border/60 pb-2 last:border-0">
                        <span class="font-medium">{{ $s->name }}</span>
                        <span class="text-xs text-muted">{{ ucfirst($s->type) }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <p class="text-xs text-muted">Les droits de validation sont paramétrables selon les délégations institutionnelles ; l'administrateur technique n'est pas valideur par défaut.</p>
    </div>
</div>
@endsection
