<?php

namespace Prodstarter\FilamentForgeInsights\Repositories;

use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\ScheduledJobData;
use Prodstarter\FilamentForgeInsights\Forge\ForgeConnector;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListScheduledJobsRequest;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ScheduledJobRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Support\ForgeCache;
use Prodstarter\FilamentForgeInsights\Support\JsonApiResource;

class ScheduledJobRepository implements ScheduledJobRepositoryInterface
{
    public function __construct(
        protected ForgeConnector $connector,
        protected ServerRepositoryInterface $servers,
    ) {}

    public function forServer(int | string $serverId): Collection
    {
        return ForgeCache::remember(
            'jobs:' . config('forge-insights.organization') . ":{$serverId}",
            config('forge-insights.cache.jobs', 600),
            fn () => collect(
                $this->connector->paginate(new ListScheduledJobsRequest($serverId))
                    ->collect()
                    ->map(fn (array $item) => ScheduledJobData::fromArray($serverId, JsonApiResource::flattenItem($item)))
                    ->all(),
            ),
        );
    }

    public function all(): Collection
    {
        return ForgeCache::remember(
            'jobs:all:' . config('forge-insights.organization'),
            config('forge-insights.cache.jobs', 600),
            fn () => $this->servers->all()
                ->flatMap(fn ($server) => $this->forServer($server->id))
                ->values(),
        );
    }
}
