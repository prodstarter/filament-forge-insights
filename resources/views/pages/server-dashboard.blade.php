<x-filament-panels::page>
    @php
        $scope = $this->getScope();
    @endphp

    @if ($scope === 'disconnected')
        @include('filament-forge-insights::pages.server-dashboard.disconnected')
    @else
        @php
            $header = $this->getIdentityHeader();
            $cards = $this->getCards();
            $attentionItems = $this->getAttentionItems();
            $deploymentActivity = $this->getDeploymentActivity();
        @endphp

        <div class="space-y-6">
            @include('filament-forge-insights::pages.server-dashboard.header', [
                'header' => $header,
                'lastCheckedAt' => $this->getLastCheckedAt(),
            ])

            @if (! empty($cards))
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($cards as $card)
                        @include('filament-forge-insights::components.status-card', ['card' => $card])
                    @endforeach
                </div>
            @endif

            @include('filament-forge-insights::pages.server-dashboard.attention', ['items' => $attentionItems])

            @if ($scope === 'site')
                <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-5">
                    <div class="lg:col-span-3">
                        @include('filament-forge-insights::pages.server-dashboard.server-overview', $this->getSiteOverview())
                    </div>
                    <div class="lg:col-span-2">
                        @include('filament-forge-insights::pages.server-dashboard.site-information', $this->getSiteOverview())
                    </div>
                </div>
            @elseif ($scope === 'server')
                @include('filament-forge-insights::pages.server-dashboard.server-overview', $this->getServerOverview())
            @else
                @include('filament-forge-insights::pages.server-dashboard.organization-overview', $this->getOrganizationOverview())
            @endif

            @include('filament-forge-insights::pages.server-dashboard.deployment-activity', $deploymentActivity)
        </div>
    @endif
</x-filament-panels::page>
