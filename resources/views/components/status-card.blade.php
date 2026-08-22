@php
    /** @var \Prodstarter\FilamentForgeInsights\Support\HealthStatus $health */
    $health = $card['health'];
@endphp

<x-filament::section compact>
    <div class="flex items-center gap-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $health->iconBackgroundClasses() }}">
            <x-filament::icon :icon="$card['icon']" class="h-5 w-5 {{ $health->iconClasses() }}" />
        </span>
        <span class="truncate text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            {{ $card['label'] }}
        </span>
    </div>

    <div class="mt-3 flex items-center gap-2">
        <span class="h-2 w-2 shrink-0 rounded-full {{ $health->dotClasses() }}"></span>
        <span class="truncate text-base font-semibold text-gray-950 dark:text-white">
            {{ $card['status'] }}
        </span>
    </div>

    @if (! empty($card['lines']))
        <div class="mt-2 space-y-0.5">
            @foreach ($card['lines'] as $line)
                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $line }}</p>
            @endforeach
        </div>
    @endif
</x-filament::section>
