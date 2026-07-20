<?php

namespace Prodstarter\FilamentForgeInsights\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\DeploymentRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SiteRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;

class ResourceOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        if (! app(SettingsManager::class)->isConnected()) {
            return [
                Stat::make('Forge connection', 'Not connected')
                    ->description('Connect an account on the Settings page')
                    ->color('danger'),
            ];
        }

        $servers = app(ServerRepositoryInterface::class)->all();
        $sites = app(SiteRepositoryInterface::class);
        $siteCount = $servers->sum(fn ($server) => $sites->all($server->id)->count());
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
