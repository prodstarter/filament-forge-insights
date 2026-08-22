<?php

namespace Prodstarter\FilamentForgeInsights\Filament\Pages\Concerns;

use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;

/**
 * For pages that only make sense when browsing a full organization's
 * portfolio (a list of servers). Once a portal is scoped down to a single
 * server — or, by extension, a single site on that server — there's only
 * ever one server to show, so the list view has nothing left to add over
 * the Overview page itself.
 */
trait HidesWhenScopedToServer
{
    public static function shouldRegisterNavigation(): bool
    {
        return ! app(SettingsManager::class)->isScopedToServer();
    }

    public static function canAccess(): bool
    {
        return ! app(SettingsManager::class)->isScopedToServer();
    }
}
