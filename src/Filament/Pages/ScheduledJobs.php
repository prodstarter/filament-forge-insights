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
use Prodstarter\FilamentForgeInsights\Data\ScheduledJobData;
use Prodstarter\FilamentForgeInsights\Filament\Pages\Concerns\HidesWhenScopedToSite;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ScheduledJobRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;
use UnitEnum;

class ScheduledJobs extends Page implements HasActions, HasTable
{
    use HidesWhenScopedToSite;
    use InteractsWithActions;
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Jobs';

    protected static ?int $navigationSort = 6;

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
                TextColumn::make('name')
                    ->label('Command')
                    ->description(fn (array $record): ?string => $record['command'])
                    ->searchable(),
                TextColumn::make('frequency'),
                TextColumn::make('cron')
                    ->label('Cron expression')
                    ->fontFamily('mono'),
                TextColumn::make('nextRunAt')
                    ->label('Next run')
                    ->dateTime(),
                TextColumn::make('isActive')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
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

        return app(ScheduledJobRepositoryInterface::class)->all()
            ->mapWithKeys(fn (ScheduledJobData $job) => [$job->id => [
                'server' => $servers->get($job->serverId)?->name,
                'name' => $job->name,
                'command' => $job->command,
                'frequency' => $job->frequency,
                'cron' => $job->cron,
                'nextRunAt' => $job->nextRunAt,
                'isActive' => $job->isActive(),
            ]]);
    }
}
