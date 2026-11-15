@props(['meta' => []])
@php $current = $meta['current_page'] ?? 1; $last = $meta['last_page'] ?? 1; @endphp
@if ($last > 1)
<nav class="mt-4 flex items-center justify-between text-sm text-muted">
    <span>Page {{ $current }} sur {{ $last }} — {{ $meta['total'] ?? '' }} élément(s)</span>
    <div class="flex gap-2">
        @if ($current > 1)
            <a href="{{ request()->fullUrlWithQuery(['page' => $current - 1]) }}" class="btn-secondary px-3! py-1.5!">← Précédent</a>
        @endif
        @if ($current < $last)
            <a href="{{ request()->fullUrlWithQuery(['page' => $current + 1]) }}" class="btn-secondary px-3! py-1.5!">Suivant →</a>
        @endif
    </div>
</nav>
@endif
