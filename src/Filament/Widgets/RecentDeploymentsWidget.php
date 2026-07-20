<?php

namespace Prodstarter\FilamentForgeInsights\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\DeploymentData;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\DeploymentRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;

class RecentDeploymentsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Deployments';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRows())
            ->columns([
                TextColumn::make('commitMessage')
                    ->label('Commit')
                    ->limit(40),
                TextColumn::make('commitBranch')
                    ->label('Branch')
                    ->badge(),
                TextColumn::make('triggeredBy')
                    ->label('Triggered by'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (array $record): string => $record['statusColor']),
                TextColumn::make('duration')
                    ->label('Duration'),
                TextColumn::make('when')
                    ->label('When'),
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

        return app(DeploymentRepositoryInterface::class)->recent(10)
            ->mapWithKeys(fn (DeploymentData $deployment) => [$deployment->id => [
                'commitMessage' => $deployment->commitMessage,
                'commitBranch' => $deployment->commitBranch,
                'triggeredBy' => $deployment->triggeredBy,
                'status' => $deployment->status,
                'statusColor' => match ($deployment->status) {
                    'finished' => 'success',
                    'failed' => 'danger',
                    default => 'gray',
                },
                'duration' => $deployment->formattedDuration(),
                'when' => $deployment->createdAt?->diffForHumans(),
            ]]);
    }
}
