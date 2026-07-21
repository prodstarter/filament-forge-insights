<?php

namespace Prodstarter\FilamentForgeInsights\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;
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
                    ->description('Connect an account on the Settings page')
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
        $isSecured = app(SslRepositoryInterface::class)
            ->forSite($server->id, $site->id)
            ->contains(fn ($certificate) => $certificate->active && $certificate->isInstalled());

        $lastDeployment = app(DeploymentRepositoryInterface::class)->recent(1)->first();

        return [
            Stat::make('Website', $site->domain)
                ->description($site->status === 'installed' ? 'Online' : ($site->status ?? 'Unknown'))
                ->color($site->status === 'installed' ? 'success' : 'danger'),
            Stat::make('Hosting', $server->provider ?? '—')
                ->description($server->region ?? ''),
            Stat::make('PHP', $site->phpVersion ?? '—'),
            Stat::make('SSL', $isSecured ? 'Valid' : 'Not secured')
                ->color($isSecured ? 'success' : 'danger'),
            Stat::make('Last deployment', $lastDeployment?->createdAt?->diffForHumans() ?? 'Never')
                ->color($lastDeployment?->isFinished() ? 'success' : 'gray'),
        ];
    }

    /**
     * @return array<int, Stat>
     */
    protected function serverStats(ServerData $server): array
    {
        $siteCount = app(SiteRepositoryInterface::class)->all($server->id)->count();
        $deploymentsToday = app(DeploymentRepositoryInterface::class)
            ->recent(50)
            ->filter(fn ($deployment) => $deployment->createdAt?->isToday())
            ->count();

        return [
            Stat::make('Server', $server->name)
                ->description($server->isOnline() ? 'Online' : 'Offline')
                ->color($server->isOnline() ? 'success' : 'danger'),
            Stat::make('Sites', (string) $siteCount),
            Stat::make('PHP', $server->phpVersion ?? '—'),
            Stat::make('Deployments today', (string) $deploymentsToday),
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
        $deploymentsToday = app(DeploymentRepositoryInterface::class)
            ->recent(50)
            ->filter(fn ($deployment) => $deployment->createdAt?->isToday())
            ->count();

        return [
            Stat::make('Forge connection', 'Connected')
                ->color('success'),
            Stat::make('Servers', (string) $servers->count()),
            Stat::make('Websites', (string) $siteCount),
            Stat::make('Deployments today', (string) $deploymentsToday),
        ];
    }
}
