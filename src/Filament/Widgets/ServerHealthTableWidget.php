<?php

namespace Prodstarter\FilamentForgeInsights\Filament\Widgets;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\ServerData;
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
            ->columns([
                TextColumn::make('name')
                    ->label('Server'),
                TextColumn::make('provider'),
                TextColumn::make('phpVersion')
                    ->label('PHP'),
                TextColumn::make('sites')
                    ->label('Sites'),
                IconColumn::make('isOnline')
                    ->label('Status')
                    ->boolean(),
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
                'provider' => $server->provider,
                'phpVersion' => $server->phpVersion,
                'sites' => $sites->all($server->id)->count(),
                'isOnline' => $server->isOnline(),
            ]]);
    }
}
