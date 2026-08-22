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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\SiteData;
use Prodstarter\FilamentForgeInsights\Filament\Pages\Concerns\HidesWhenScopedToSite;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SiteRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;
use UnitEnum;

class ListSites extends Page implements HasActions, HasTable
{
    use HidesWhenScopedToSite;
    use InteractsWithActions;
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Sites';

    protected static ?int $navigationSort = 2;

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
            ->records(fn (int $page, int $recordsPerPage): LengthAwarePaginator => $this->paginateRows($page, $recordsPerPage))
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('domain')
                    ->searchable()
                    ->url(fn (array $record): ?string => $record['url'])
                    ->copyable()
                    ->copyMessage('Domain copied'),
                TextColumn::make('server')
                    ->searchable(),
                TextColumn::make('repository')
                    ->label('Repository')
                    ->searchable()
                    ->url(fn (array $record): ?string => $record['repository'])
                    ->openUrlInNewTab()
                    ->icon(fn (array $record): ?Heroicon => filled($record['repository']) ? Heroicon::OutlinedCodeBracket : null),
                TextColumn::make('branch'),
                TextColumn::make('quickDeploy')
                    ->label('Quick deploy')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Enabled' : 'Disabled')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('isSecured')
                    ->label('SSL')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Secured' : 'Not secured')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                TextColumn::make('status')
                    ->badge(),
            ]);
    }

    protected function paginateRows(int $page, int $recordsPerPage): LengthAwarePaginator
    {
        $rows = $this->getRows();

        return new LengthAwarePaginator(
            $rows->forPage($page, $recordsPerPage),
            total: $rows->count(),
            perPage: $recordsPerPage,
            currentPage: $page,
        );
    }

    /**
     * @return Collection<int|string, array<string, mixed>>
     */
    protected function getRows(): Collection
    {
        if (! app(SettingsManager::class)->isConnected()) {
            return collect();
        }

        $servers = app(ServerRepositoryInterface::class)->all();
        $sites = app(SiteRepositoryInterface::class);

        return $servers
            ->flatMap(fn ($server) => $sites->all($server->id)
                ->map(fn (SiteData $site) => [$server->name, $site]))
            ->mapWithKeys(fn (array $pair) => [$pair[1]->id => [
                'domain' => $pair[1]->domain,
                'url' => $pair[1]->url,
                'server' => $pair[0],
                'repository' => $pair[1]->repositoryUrl,
                'branch' => $pair[1]->repositoryBranch,
                'quickDeploy' => $pair[1]->quickDeploy,
                'isSecured' => $pair[1]->isSecured,
                'status' => $pair[1]->status,
            ]]);
    }
}
