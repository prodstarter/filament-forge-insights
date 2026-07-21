<?php

namespace Prodstarter\FilamentForgeInsights\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Prodstarter\FilamentForgeInsights\Filament\Widgets\RecentDeploymentsWidget;
use Prodstarter\FilamentForgeInsights\Filament\Widgets\ResourceOverviewWidget;
use Prodstarter\FilamentForgeInsights\Filament\Widgets\ServerHealthTableWidget;
use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;
use UnitEnum;

class ServerDashboard extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -1;

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return config('forge-insights.navigation_group', 'Server');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(1)->schema(
                $this->getWidgetsSchemaComponents([
                    ResourceOverviewWidget::class,
                    ...(app(SettingsManager::class)->isScopedToSite() ? [] : [ServerHealthTableWidget::class]),
                    RecentDeploymentsWidget::class,
                ]),
            ),
        ]);
    }
}
