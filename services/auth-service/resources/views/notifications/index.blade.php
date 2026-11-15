@extends('layouts.app')

@section('title', 'Notifications — PatriGest')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-navy">Notifications</h1>
    <p class="mt-1 text-sm text-muted">Demandes en attente vous concernant</p>
</div>

<div class="card overflow-x-auto p-0">
    <table class="w-full">
        <thead>
            <tr class="border-b border-border">
                <th class="th">Notification</th>
                <th class="th">Détail</th>
                <th class="th">Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr class="border-b border-border/60 last:border-0">
                    <td class="td font-medium">{{ $item['label'] }}</td>
                    <td class="td text-muted">{{ $item['detail'] }}</td>
                    <td class="td text-muted">{{ $item['date'] ? \Illuminate\Support\Carbon::parse($item['date'])->diffForHumans() : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="td py-8 text-center text-muted">Aucune notification.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
