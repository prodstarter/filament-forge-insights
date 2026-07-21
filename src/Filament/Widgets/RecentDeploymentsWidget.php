<?php

namespace Prodstarter\FilamentForgeInsights\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\DeploymentData;
use Prodstarter\FilamentForgeInsights\Filament\Pages\Settings;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\DeploymentRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;

class RecentDeploymentsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Deployments';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => $this->getRows())
            ->paginated(false)
            ->striped()
            ->columns([
                TextColumn::make('commitMessage')
                    ->label('Commit')
                    ->icon(Heroicon::OutlinedCodeBracket)
                    ->weight('medium')
                    ->description(fn (array $record): ?string => $record['commitHash'])
                    ->limit(40),
                TextColumn::make('commitBranch')
                    ->label('Branch')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('triggeredBy')
                    ->label('Triggered by'),
                TextColumn::make('status')
                    ->badge()
                    ->icon(fn (array $record): Heroicon => match ($record['status']) {
                        'finished' => Heroicon::OutlinedCheckCircle,
                        'failed' => Heroicon::OutlinedXCircle,
                        default => Heroicon::OutlinedClock,
                    })
                    ->color(fn (array $record): string => $record['statusColor']),
                TextColumn::make('duration')
                    ->label('Duration')
                    ->icon(Heroicon::OutlinedClock),
                TextColumn::make('when')
                    ->label('When'),
            ])
            ->emptyStateHeading('No deployments yet')
            ->emptyStateDescription('Deployments will show up here once your connected account has some history.')
            ->emptyStateIcon(Heroicon::OutlinedRocketLaunch)
            ->emptyStateActions([
                Action::make('connect')
                    ->label('Go to Settings')
                    ->url(fn (): string => Settings::getUrl())
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->visible(fn (): bool => ! app(SettingsManager::class)->isConnected()),
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
                'commitHash' => $deployment->commitHash ? substr($deployment->commitHash, 0, 7) : null,
                'commitBranch' => $deployment->commitBranch,
                'triggeredBy' => $deployment->triggeredBy,
                'status' => $deployment->status,
                'statusColor' => $deployment->statusColor(),
                'duration' => $deployment->formattedDuration(),
                'when' => $deployment->createdAt?->diffForHumans(),
            ]]);
    }
}
