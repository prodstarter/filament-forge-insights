@php
    /** @var ?\Prodstarter\FilamentForgeInsights\Data\SiteData $site */
    /** @var ?\Prodstarter\FilamentForgeInsights\Data\SslCertificateData $ssl */
    $sslHealth = $ssl?->health() ?? \Prodstarter\FilamentForgeInsights\Support\HealthStatus::Unknown;
@endphp

<x-filament::section icon="heroicon-o-globe-alt" heading="Site Information">
    @if ($site)
        <dl>
            @include('filament-forge-insights::components.detail-row', ['label' => 'Domain', 'value' => $site->domain])

            @if ($site->repositoryProvider)
                @include('filament-forge-insights::components.detail-row', ['label' => 'Repository', 'value' => $site->repositoryProvider])
            @endif

            @if ($site->repositoryBranch)
                <div class="flex items-center justify-between gap-4 border-b border-gray-100 py-2 last:border-0 dark:border-white/5">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Branch</dt>
                    <dd><x-filament::badge color="gray">{{ $site->repositoryBranch }}</x-filament::badge></dd>
                </div>
            @endif

            @if ($site->phpVersion)
                @include('filament-forge-insights::components.detail-row', ['label' => 'PHP Version', 'value' => $site->phpVersion])
            @endif

            @if ($site->appType)
                @include('filament-forge-insights::components.detail-row', ['label' => 'App Type', 'value' => $site->appType])
            @endif

            <div class="flex items-center justify-between gap-4 py-2">
                <dt class="text-sm text-gray-500 dark:text-gray-400">SSL</dt>
                <dd>
                    <span class="inline-flex items-center gap-1.5 text-sm font-medium {{ $sslHealth->textClasses() }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $sslHealth->dotClasses() }}"></span>
                        {{ $site->isSecured ? 'Valid' : 'Not secured' }}
                    </span>
                </dd>
            </div>
        </dl>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">No site data available.</p>
    @endif
</x-filament::section>
