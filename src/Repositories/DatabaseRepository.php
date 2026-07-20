<?php

namespace Prodstarter\FilamentForgeInsights\Repositories;

use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\DatabaseData;
use Prodstarter\FilamentForgeInsights\Forge\ForgeConnector;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListDatabasesRequest;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\DatabaseRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Support\ForgeCache;
use Prodstarter\FilamentForgeInsights\Support\JsonApiResource;

class DatabaseRepository implements DatabaseRepositoryInterface
{
    public function __construct(
        protected ForgeConnector $connector,
        protected ServerRepositoryInterface $servers,
    ) {}

    public function forServer(int | string $serverId): Collection
    {
        return ForgeCache::remember(
            'databases:' . config('forge-insights.organization') . ":{$serverId}",
            config('forge-insights.cache.databases', 600),
            fn () => collect(
                $this->connector->paginate(new ListDatabasesRequest($serverId))
                    ->collect()
                    ->map(fn (array $item) => DatabaseData::fromArray($serverId, JsonApiResource::flattenItem($item)))
                    ->all(),
            ),
        );
    }

    public function all(): Collection
    {
        return ForgeCache::remember(
            'databases:all:' . config('forge-insights.organization'),
            config('forge-insights.cache.databases', 600),
            fn () => $this->servers->all()
                ->flatMap(fn ($server) => $this->forServer($server->id))
                ->values(),
        );
    }
}
