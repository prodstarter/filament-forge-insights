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
use Prodstarter\FilamentForgeInsights\Data\DeploymentData;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\DeploymentRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SiteRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;
use UnitEnum;

class ListDeployments extends Page implements HasActions, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?string $navigationLabel = 'Deployments';

    protected static ?int $navigationSort = 3;

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
                TextColumn::make('site')
                    ->searchable(),
                TextColumn::make('commitMessage')
                    ->label('Commit')
                    ->description(fn (array $record): ?string => $record['commitHash'] ? substr($record['commitHash'], 0, 7) : null)
                    ->searchable(),
                TextColumn::make('commitBranch')
                    ->label('Branch')
                    ->badge(),
                TextColumn::make('triggeredBy')
                    ->label('Triggered by'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (array $record): string => match ($record['status']) {
                        'finished' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('duration'),
                TextColumn::make('when'),
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

        $sites = app(ServerRepositoryInterface::class)->all()
            ->flatMap(fn ($server) => app(SiteRepositoryInterface::class)->all($server->id))
            ->keyBy('id');

        return app(DeploymentRepositoryInterface::class)->recent(50)
            ->mapWithKeys(fn (DeploymentData $deployment) => [$deployment->id => [
                'site' => $sites->get($deployment->siteId)?->domain,
                'commitMessage' => $deployment->commitMessage,
                'commitHash' => $deployment->commitHash,
                'commitBranch' => $deployment->commitBranch,
                'triggeredBy' => $deployment->triggeredBy,
                'status' => $deployment->status,
                'duration' => $deployment->formattedDuration(),
                'when' => $deployment->createdAt?->diffForHumans(),
            ]]);
    }
}
