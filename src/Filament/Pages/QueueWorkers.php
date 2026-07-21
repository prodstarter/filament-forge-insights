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
use Prodstarter\FilamentForgeInsights\Data\WorkerData;
use Prodstarter\FilamentForgeInsights\Filament\Pages\Concerns\HidesWhenScopedToSite;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\WorkerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;
use UnitEnum;

class QueueWorkers extends Page implements HasActions, HasTable
{
    use HidesWhenScopedToSite;
    use InteractsWithActions;
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $navigationLabel = 'Workers';

    protected static ?int $navigationSort = 7;

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return config('forge-insights.navigation_group', 'Server');
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
                TextColumn::make('server')
                    ->searchable(),
                TextColumn::make('command')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('user'),
                TextColumn::make('processes')
                    ->label('Processes'),
                TextColumn::make('isRunning')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Running' : 'Stopped')
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

        $servers = app(ServerRepositoryInterface::class)->all()->keyBy('id');

        return app(WorkerRepositoryInterface::class)->all()
            ->mapWithKeys(fn (WorkerData $worker) => [$worker->id => [
                'server' => $servers->get($worker->serverId)?->name,
                'command' => $worker->command,
                'user' => $worker->user,
                'processes' => $worker->processes,
                'isRunning' => $worker->isRunning(),
            ]]);
    }
}
