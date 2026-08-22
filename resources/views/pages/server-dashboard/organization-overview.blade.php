@php
    /** @var \Illuminate\Support\Collection $servers */
    /** @var int $totalCount */
    /** @var string $viewAllUrl */
@endphp

<x-filament::section icon="heroicon-o-server-stack" heading="Servers">
    @if ($totalCount > $servers->count())
        <x-slot name="afterHeader">
            <x-filament::button tag="a" :href="$viewAllUrl" color="gray" size="sm" icon="heroicon-o-arrow-right" icon-position="after">
                View all servers
            </x-filament::button>
        </x-slot>
    @endif

    @forelse ($servers as $row)
        @php
            /** @var \Prodstarter\FilamentForgeInsights\Data\ServerData $server */
            $server = $row['server'];
            $health = $server->isOnline() ? \Prodstarter\FilamentForgeInsights\Support\HealthStatus::Healthy : \Prodstarter\FilamentForgeInsights\Support\HealthStatus::Critical;
        @endphp
        <div class="flex items-center justify-between gap-4 border-b border-gray-100 py-3 last:border-0 dark:border-white/5">
            <div class="flex items-center gap-3">
                <span class="h-2 w-2 shrink-0 rounded-full {{ $health->dotClasses() }}"></span>
                <div>
                    <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $server->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ implode(' · ', array_filter([$server->formattedProvider(), $server->region])) }}</p>
                </div>
            </div>
            <div class="text-right">
                <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $row['siteCount'] }} {{ \Illuminate\Support\Str::plural('site', $row['siteCount']) }}</p>
                <p class="text-xs {{ $health->textClasses() }}">{{ $health->label() }}</p>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">No servers found.</p>
    @endforelse
</x-filament::section>
