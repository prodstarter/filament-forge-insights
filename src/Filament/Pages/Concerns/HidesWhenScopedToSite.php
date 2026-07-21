<?php

namespace Prodstarter\FilamentForgeInsights\Filament\Pages\Concerns;

use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;

/**
 * For pages backed by server-wide Forge resources that can't be attributed
 * to a single site (scheduled jobs, background processes, databases). When
 * a portal is scoped down to one site on a shared server, showing these
 * would leak data belonging to other clients hosted on the same server.
 */
trait HidesWhenScopedToSite
{
    public static function shouldRegisterNavigation(): bool
    {
        return ! app(SettingsManager::class)->isScopedToSite();
    }

    public static function canAccess(): bool
    {
        return ! app(SettingsManager::class)->isScopedToSite();
    }
}
