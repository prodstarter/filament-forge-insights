@php
    /** @var \Prodstarter\FilamentForgeInsights\Support\HealthStatus $health */
    $health = $header['health'];
@endphp

<div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
    <div>
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-950 dark:text-white">
                {{ $header['title'] }}
            </h1>

            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $health->iconBackgroundClasses() }} {{ $health->textClasses() }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $health->dotClasses() }}"></span>
                {{ $health->label() }}
            </span>
        </div>

        @if ($header['subtitle'])
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $header['subtitle'] }}</p>
        @endif
    </div>

    <div class="flex shrink-0 items-center gap-3">
        <span class="inline-flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
            <x-filament::icon icon="heroicon-o-arrow-path" class="h-3.5 w-3.5" />
            Last checked {{ $lastCheckedAt->diffForHumans() }}
        </span>

        @if ($header['websiteUrl'])
            <x-filament::button
                tag="a"
                :href="$header['websiteUrl']"
                target="_blank"
                icon="heroicon-o-arrow-top-right-on-square"
                icon-position="after"
                color="gray"
            >
                View Website
            </x-filament::button>
        @endif
    </div>
</div>
