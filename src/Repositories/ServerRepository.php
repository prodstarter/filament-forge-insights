<?php

namespace Prodstarter\FilamentForgeInsights\Repositories;

use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\ServerData;
use Prodstarter\FilamentForgeInsights\Forge\ForgeConnector;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListServersRequest;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Support\ForgeCache;
use Prodstarter\FilamentForgeInsights\Support\JsonApiResource;

class ServerRepository implements ServerRepositoryInterface
{
    public function __construct(
        protected ForgeConnector $connector,
    ) {}

    public function all(): Collection
    {
        return ForgeCache::remember(
            'servers:' . config('forge-insights.organization'),
            config('forge-insights.cache.servers', 600),
            fn () => collect(
                $this->connector->paginate(new ListServersRequest)
                    ->collect()
                    ->map(fn (array $item) => ServerData::fromArray(JsonApiResource::flattenItem($item)))
                    ->all(),
            ),
        );
    }

    public function find(int | string $id): ?ServerData
    {
        return $this->all()->first(fn (ServerData $server) => (string) $server->id === (string) $id);
    }
}
