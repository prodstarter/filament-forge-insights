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
use Prodstarter\FilamentForgeInsights\Data\DatabaseData;
use Prodstarter\FilamentForgeInsights\Filament\Pages\Concerns\HidesWhenScopedToSite;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\DatabaseRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;
use UnitEnum;

class ListDatabases extends Page implements HasActions, HasTable
{
    use HidesWhenScopedToSite;
    use InteractsWithActions;
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $navigationLabel = 'Databases';

    protected static ?int $navigationSort = 5;

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
                TextColumn::make('server')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Database')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('createdAt')
                    ->label('Created')
                    ->date(),
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

        $servers = app(ServerRepositoryInterface::class)->all()->keyBy('id');

        return app(DatabaseRepositoryInterface::class)->all()
            ->mapWithKeys(fn (DatabaseData $database) => [$database->id => [
                'server' => $servers->get($database->serverId)?->name,
                'name' => $database->name,
                'status' => $database->status,
                'createdAt' => $database->createdAt,
            ]]);
    }
}
