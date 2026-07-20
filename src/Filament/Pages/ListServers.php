<?php

namespace Prodstarter\FilamentForgeInsights\Filament\Pages;

use BackedEnum;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\ServerData;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SiteRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;
use UnitEnum;

class ListServers extends Page implements HasActions, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedServer;

    protected static ?string $navigationLabel = 'Servers';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return config('forge-insights.navigation_group', 'Infrastructure');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRows())
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('provider'),
                TextColumn::make('region'),
                TextColumn::make('size'),
                TextColumn::make('ipAddress')
                    ->label('IP address')
                    ->copyable()
                    ->copyMessage('IP address copied'),
                TextColumn::make('phpVersion')
                    ->label('PHP'),
                TextColumn::make('databaseType')
                    ->label('Database'),
                TextColumn::make('sites')
                    ->label('Sites'),
                TextColumn::make('isOnline')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Online' : 'Offline')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
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
                'region' => $server->region,
                'size' => $server->size,
                'ipAddress' => $server->ipAddress,
                'phpVersion' => $server->phpVersion,
                'databaseType' => $server->databaseType,
                'sites' => $sites->all($server->id)->count(),
                'isOnline' => $server->isOnline(),
            ]]);
    }
}
