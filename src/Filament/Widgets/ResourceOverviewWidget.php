<?php

namespace Prodstarter\FilamentForgeInsights\Filament\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\DeploymentData;
use Prodstarter\FilamentForgeInsights\Data\ServerData;
use Prodstarter\FilamentForgeInsights\Data\SiteData;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\DeploymentRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SiteRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SslRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;

class ResourceOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $manager = app(SettingsManager::class);

        if (! $manager->isConnected()) {
            return [
                Stat::make('Forge connection', 'Not connected')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->description('Connect an account on the Settings page')
                    ->descriptionIcon(Heroicon::OutlinedArrowRight)
                    ->color('danger'),
            ];
        }

        $servers = app(ServerRepositoryInterface::class)->all();

        if ($manager->isScopedToSite() && $servers->isNotEmpty()) {
            $server = $servers->first();
            $site = app(SiteRepositoryInterface::class)->all($server->id)->first();

            if ($site) {
                return $this->siteStats($server, $site);
            }
        }

        if ($manager->isScopedToServer() && $servers->isNotEmpty()) {
            return $this->serverStats($servers->first());
        }

        return $this->organizationStats($servers);
    }

    /**
     * @return array<int, Stat>
     */
    protected function siteStats(ServerData $server, SiteData $site): array
    {
        $isOnline = $site->status === 'installed';

        $isSecured = app(SslRepositoryInterface::class)
            ->forSite($server->id, $site->id)
            ->contains(fn ($certificate) => $certificate->active && $certificate->isInstalled());

        $lastDeployment = app(DeploymentRepositoryInterface::class)->recent(1)->first();

        return [
            Stat::make('Website', $site->domain)
                ->icon(Heroicon::OutlinedGlobeAlt)
                ->description($isOnline ? 'Online' : ($site->status ?? 'Unknown'))
                ->descriptionIcon($isOnline ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedExclamationTriangle)
                ->color($isOnline ? 'success' : 'danger'),
            Stat::make('Hosting', $server->provider ?? '—')
                ->icon(Heroicon::OutlinedCloudArrowUp)
                ->description($server->region ?? ''),
            Stat::make('PHP', $site->phpVersion ?? '—')
                ->icon(Heroicon::OutlinedCodeBracket),
            Stat::make('SSL', $isSecured ? 'Valid' : 'Not secured')
                ->icon(Heroicon::OutlinedLockClosed)
                ->descriptionIcon($isSecured ? Heroicon::OutlinedShieldCheck : Heroicon::OutlinedShieldExclamation)
                ->color($isSecured ? 'success' : 'danger'),
            Stat::make('Last deployment', $lastDeployment?->createdAt?->diffForHumans() ?? 'Never')
                ->icon(Heroicon::OutlinedRocketLaunch)
                ->description($lastDeployment?->commitMessage ?? '')
                ->color($lastDeployment?->isFinished() ? 'success' : 'gray'),
        ];
    }

    /**
     * @return array<int, Stat>
     */
    protected function serverStats(ServerData $server): array
    {
        $siteCount = app(SiteRepositoryInterface::class)->all($server->id)->count();
        $recentDeployments = app(DeploymentRepositoryInterface::class)->recent(50);
        $deploymentsToday = $recentDeployments->filter(fn ($deployment) => $deployment->createdAt?->isToday())->count();

        return [
            Stat::make('Server', $server->name)
                ->icon(Heroicon::OutlinedServer)
                ->description($server->isOnline() ? 'Online' : 'Offline')
                ->descriptionIcon($server->isOnline() ? Heroicon::OutlinedWifi : Heroicon::OutlinedSignalSlash)
                ->color($server->isOnline() ? 'success' : 'danger'),
            Stat::make('Sites', (string) $siteCount)
                ->icon(Heroicon::OutlinedGlobeAlt),
            Stat::make('PHP', $server->phpVersion ?? '—')
                ->icon(Heroicon::OutlinedCodeBracket),
            Stat::make('Deployments today', (string) $deploymentsToday)
                ->icon(Heroicon::OutlinedRocketLaunch)
                ->chart($this->deploymentsPerDay($recentDeployments))
                ->chartColor('success'),
        ];
    }

    /**
     * @param  Collection<int, ServerData>  $servers
     * @return array<int, Stat>
     */
    protected function organizationStats($servers): array
    {
        $sites = app(SiteRepositoryInterface::class);
        $siteCount = $servers->sum(fn (ServerData $server) => $sites->all($server->id)->count());
        $recentDeployments = app(DeploymentRepositoryInterface::class)->recent(50);
        $deploymentsToday = $recentDeployments->filter(fn ($deployment) => $deployment->createdAt?->isToday())->count();

        return [
            Stat::make('Forge connection', 'Connected')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->color('success'),
            Stat::make('Servers', (string) $servers->count())
                ->icon(Heroicon::OutlinedServerStack),
            Stat::make('Websites', (string) $siteCount)
                ->icon(Heroicon::OutlinedGlobeAlt),
            Stat::make('Deployments today', (string) $deploymentsToday)
                ->icon(Heroicon::OutlinedRocketLaunch)
                ->chart($this->deploymentsPerDay($recentDeployments))
                ->chartColor('success'),
        ];
    }

    /**
     * Bucket the last 7 days of deployments into a daily count, oldest first,
     * for the "Deployments today" stat's trend sparkline.
     *
     * @param  Collection<int, DeploymentData>  $deployments
     * @return array<int, int>
     */
    protected function deploymentsPerDay(Collection $deployments): array
    {
        $today = now()->startOfDay();

        return collect(range(6, 0))
            ->map(function (int $daysAgo) use ($deployments, $today) {
                $day = $today->copy()->subDays($daysAgo);

                return $deployments->filter(
                    fn (DeploymentData $deployment) => $deployment->createdAt?->isSameDay($day),
                )->count();
            })
            ->all();
    }
}
