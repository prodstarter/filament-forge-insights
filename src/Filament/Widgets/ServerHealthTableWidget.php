<?php

namespace Prodstarter\FilamentForgeInsights\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\ServerData;
use Prodstarter\FilamentForgeInsights\Filament\Pages\Settings;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SiteRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;

class ServerHealthTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Server Health';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRows())
            ->paginated(false)
            ->striped()
            ->columns([
                TextColumn::make('name')
                    ->label('Server')
                    ->icon(Heroicon::OutlinedServer)
                    ->weight('medium')
                    ->description(fn (array $record): ?string => $record['ipAddress'])
                    ->searchable(),
                TextColumn::make('provider'),
                TextColumn::make('phpVersion')
                    ->label('PHP')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('sites')
                    ->label('Sites')
                    ->icon(Heroicon::OutlinedGlobeAlt),
                TextColumn::make('isOnline')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Online' : 'Offline')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
            ])
            ->emptyStateHeading('Not connected to Forge')
            ->emptyStateDescription('Connect a Forge account to see your server health here.')
            ->emptyStateIcon(Heroicon::OutlinedServerStack)
            ->emptyStateActions([
                Action::make('connect')
                    ->label('Go to Settings')
                    ->url(fn (): string => Settings::getUrl())
                    ->icon(Heroicon::OutlinedCog6Tooth),
            ]);
    }

    /**
     * @return Collection<int|string, array<string, mixed>>
     */
    protected function getRows(): Collection
    {
        if (! app(SettingsManager::class)->isConnected()) {
            return collect();
        }

        $sites = app(SiteRepositoryInterface::class);

        return app(ServerRepositoryInterface::class)->all()
            ->mapWithKeys(fn (ServerData $server) => [$server->id => [
                'name' => $server->name,
                'ipAddress' => $server->ipAddress,
                'provider' => $server->provider,
                'phpVersion' => $server->phpVersion,
                'sites' => $sites->all($server->id)->count(),
                'isOnline' => $server->isOnline(),
            ]]);
    }
}
