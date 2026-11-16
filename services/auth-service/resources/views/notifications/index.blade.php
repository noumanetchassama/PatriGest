@extends('layouts.app')

@section('title', 'Notifications — PatriGest')

@section('content')
<div class="mb-6 flex items-start justify-between">
    <div>
        <h1 class="text-2xl font-bold text-navy">Notifications</h1>
        <p class="mt-1 text-sm text-muted">Décisions et événements vous concernant</p>
    </div>
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button class="btn-secondary text-sm">Tout marquer comme lu</button>
    </form>
</div>

@if (session('success'))
    <div class="mb-4 rounded-lg border border-success/40 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
@endif
@if ($apiError ?? false)
    <div class="mb-4 rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-danger">Service de notifications indisponible.</div>
@endif

<div class="card overflow-x-auto p-0">
    <table class="w-full">
        <thead>
            <tr class="border-b border-border">
                <th class="th">Notification</th>
                <th class="th">Message</th>
                <th class="th">Date</th>
                <th class="th text-right">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $n)
                <tr class="border-b border-border/60 last:border-0 {{ $n['read'] ? '' : 'bg-primary-light/40' }}">
                    <td class="td font-medium">
                        @unless ($n['read'])<span class="mr-1 inline-block h-2 w-2 rounded-full bg-primary"></span>@endunless
                        {{ $n['title'] }}
                    </td>
                    <td class="td text-muted">{{ $n['message'] ?? '—' }}</td>
                    <td class="td text-muted">{{ $n['created_at'] ? \Illuminate\Support\Carbon::parse($n['created_at'])->diffForHumans() : '—' }}</td>
                    <td class="td text-right">
                        <form method="POST" action="{{ route('notifications.read', $n['id']) }}">
                            @csrf
                            <input type="hidden" name="link" value="{{ $n['link'] ?? '' }}">
                            <button class="text-sm font-medium text-primary hover:underline">{{ $n['link'] ? 'Ouvrir' : 'Marquer lu' }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="td py-8 text-center text-muted">Aucune notification.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
