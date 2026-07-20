<?php

namespace Prodstarter\FilamentForgeInsights\Repositories;

use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\WorkerData;
use Prodstarter\FilamentForgeInsights\Forge\ForgeConnector;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListWorkersRequest;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\WorkerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Support\ForgeCache;
use Prodstarter\FilamentForgeInsights\Support\JsonApiResource;

class WorkerRepository implements WorkerRepositoryInterface
{
    public function __construct(
        protected ForgeConnector $connector,
        protected ServerRepositoryInterface $servers,
    ) {}

    public function forServer(int | string $serverId): Collection
    {
        return ForgeCache::remember(
            'workers:' . config('forge-insights.organization') . ":{$serverId}",
            config('forge-insights.cache.workers', 600),
            fn () => collect(
                $this->connector->paginate(new ListWorkersRequest($serverId))
                    ->collect()
                    ->map(fn (array $item) => WorkerData::fromArray($serverId, JsonApiResource::flattenItem($item)))
                    ->all(),
            ),
        );
    }

    public function all(): Collection
    {
        return ForgeCache::remember(
            'workers:all:' . config('forge-insights.organization'),
            config('forge-insights.cache.workers', 600),
            fn () => $this->servers->all()
                ->flatMap(fn ($server) => $this->forServer($server->id))
                ->values(),
        );
    }
}
