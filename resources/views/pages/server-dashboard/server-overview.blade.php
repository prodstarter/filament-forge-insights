@php
    /** @var ?\Prodstarter\FilamentForgeInsights\Data\ServerData $server */
@endphp

<x-filament::section icon="heroicon-o-server-stack" heading="Server Overview">
    @if ($server)
        <dl>
            @include('filament-forge-insights::components.detail-row', ['label' => 'Server Name', 'value' => $server->name])
            @include('filament-forge-insights::components.detail-row', ['label' => 'Provider', 'value' => $server->formattedProvider() ?? '—'])
            @include('filament-forge-insights::components.detail-row', ['label' => 'Region', 'value' => $server->region ?? '—'])

            <div class="flex items-center justify-between gap-4 border-b border-gray-100 py-2 last:border-0 dark:border-white/5">
                <dt class="text-sm text-gray-500 dark:text-gray-400">IP Address</dt>
                <dd class="flex items-center gap-2 text-sm font-medium text-gray-950 dark:text-white">
                    <span class="font-mono">
                        {{ $ipRevealed ? ($server->ipAddress ?? '—') : '••••••••••••' }}
                    </span>
                    @if ($server->ipAddress)
                        <button
                            type="button"
                            wire:click="toggleIpVisibility"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <x-filament::icon :icon="$ipRevealed ? 'heroicon-o-eye-slash' : 'heroicon-o-eye'" class="h-4 w-4" />
                        </button>
                    @endif
                </dd>
            </div>

            @include('filament-forge-insights::components.detail-row', ['label' => 'PHP Version', 'value' => $server->formattedPhpVersion() ?? '—'])
            @include('filament-forge-insights::components.detail-row', ['label' => 'Database', 'value' => $server->formattedDatabaseType() ?? '—'])

            @if ($server->ubuntuVersion)
                @include('filament-forge-insights::components.detail-row', ['label' => 'Operating System', 'value' => 'Ubuntu ' . $server->ubuntuVersion])
            @endif

            <div class="flex items-center justify-between gap-4 py-2">
                <dt class="text-sm text-gray-500 dark:text-gray-400">Forge Status</dt>
                <dd>
                    <span class="inline-flex items-center gap-1.5 text-sm font-medium {{ ($server->isOnline() ? \Prodstarter\FilamentForgeInsights\Support\HealthStatus::Healthy : \Prodstarter\FilamentForgeInsights\Support\HealthStatus::Critical)->textClasses() }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ ($server->isOnline() ? \Prodstarter\FilamentForgeInsights\Support\HealthStatus::Healthy : \Prodstarter\FilamentForgeInsights\Support\HealthStatus::Critical)->dotClasses() }}"></span>
                        {{ $server->isOnline() ? 'Connected' : 'Not connected' }}
                    </span>
                </dd>
            </div>
        </dl>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">No server data available.</p>
    @endif
</x-filament::section>
