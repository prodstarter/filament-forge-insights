<?php

namespace Prodstarter\FilamentForgeInsights\Repositories;

use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\DeploymentData;
use Prodstarter\FilamentForgeInsights\Forge\ForgeConnector;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListDeploymentsRequest;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\DeploymentRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SiteRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Support\ForgeCache;
use Prodstarter\FilamentForgeInsights\Support\JsonApiResource;

class DeploymentRepository implements DeploymentRepositoryInterface
{
    public function __construct(
        protected ForgeConnector $connector,
        protected ServerRepositoryInterface $servers,
        protected SiteRepositoryInterface $sites,
    ) {}

    public function forSite(int | string $serverId, int | string $siteId): Collection
    {
        return ForgeCache::remember(
            'deployments:' . config('forge-insights.organization') . ":{$serverId}:{$siteId}",
            config('forge-insights.cache.deployments', 120),
            fn () => collect(
                $this->connector->paginate(new ListDeploymentsRequest($serverId, $siteId))
                    ->collect()
                    ->map(fn (array $item) => DeploymentData::fromArray($serverId, $siteId, JsonApiResource::flattenItem($item)))
                    ->all(),
            ),
        );
    }

    public function recent(int $limit = 10): Collection
    {
        return ForgeCache::remember(
            'deployments:recent:' . config('forge-insights.organization') . ":{$limit}",
            config('forge-insights.cache.deployments', 120),
            function () use ($limit) {
                $deployments = collect();

                foreach ($this->servers->all() as $server) {
                    foreach ($this->sites->all($server->id) as $site) {
                        $deployments = $deployments->merge($this->forSite($server->id, $site->id));
                    }
                }

                return $deployments
                    ->sortByDesc(fn (DeploymentData $deployment) => $deployment->createdAt)
                    ->take($limit)
                    ->values();
            },
        );
    }
}
