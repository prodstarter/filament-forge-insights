<?php

namespace Prodstarter\FilamentForgeInsights\Repositories;

use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\DeploymentData;
use Prodstarter\FilamentForgeInsights\Data\ServerData;
use Prodstarter\FilamentForgeInsights\Data\SiteData;
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
            static::cacheKey($serverId, $siteId),
            config('forge-insights.cache.deployments', 120),
            fn () => $this->fetch($serverId, $siteId),
        );
    }

    public function recent(int $limit = 10): Collection
    {
        return ForgeCache::remember(
            'deployments:recent:' . config('forge-insights.organization') . ":{$limit}",
            config('forge-insights.cache.deployments', 120),
            function () use ($limit) {
                $servers = $this->servers->all();
                $sitesByServer = $this->sites->allForServers($servers->pluck('id'));

                /** @var Collection<int, array{serverId: int|string, siteId: int|string}> $pairs */
                $pairs = $servers->flatMap(
                    fn (ServerData $server) => $sitesByServer->get($server->id, collect())
                        ->map(fn (SiteData $site) => ['serverId' => $server->id, 'siteId' => $site->id]),
                )->values();

                return $this->fetchForPairs($pairs)
                    ->sortByDesc(fn (DeploymentData $deployment) => $deployment->createdAt)
                    ->take($limit)
                    ->values();
            },
        );
    }

    /**
     * Reads whatever's already cached for each pair directly, then sends
     * one request per *uncached* pair concurrently instead of looping
     * through forSite() one pair at a time — recent() otherwise dominates
     * page load time on any organization with more than a handful of
     * sites, since every site needs its own deployment history call.
     * Reading with get() instead of has() + forSite() (i.e. remember())
     * means each pair costs one cache read here instead of two. Only the
     * first page is fetched this way (deployment history pages rarely go
     * past one); a pair with more than one page falls back to forSite()'s
     * normal, fully-paginated fetch.
     *
     * @param  Collection<int, array{serverId: int|string, siteId: int|string}>  $pairs
     * @return Collection<int, DeploymentData>
     */
    protected function fetchForPairs(Collection $pairs): Collection
    {
        /** @var Collection<int, array{serverId: int|string, siteId: int|string, cached: ?Collection<int, DeploymentData>}> $lookups */
        $lookups = $pairs->map(fn (array $pair) => [
            ...$pair,
            'cached' => ForgeCache::get(static::cacheKey($pair['serverId'], $pair['siteId'])),
        ]);

        $cachedResults = $lookups
            ->reject(fn (array $lookup) => is_null($lookup['cached']))
            ->flatMap(fn (array $lookup) => $lookup['cached']);

        $uncachedPairs = $lookups
            ->filter(fn (array $lookup) => is_null($lookup['cached']))
            ->values();

        if ($uncachedPairs->isEmpty()) {
            return $cachedResults;
        }

        $requests = $uncachedPairs->mapWithKeys(
            fn (array $pair, int $index) => [$index => new ListDeploymentsRequest($pair['serverId'], $pair['siteId'])],
        );

        $responses = $this->connector->poolRequests($requests->all());

        $freshResults = collect($responses)->flatMap(function ($response, int $index) use ($uncachedPairs) {
            $pair = $uncachedPairs[$index];

            if (filled($response->json('meta.next_cursor'))) {
                // More than one page — let forSite() paginate it properly instead of guessing.
                return $this->forSite($pair['serverId'], $pair['siteId']);
            }

            $deployments = collect($response->json('data') ?? [])
                ->map(fn (array $item) => DeploymentData::fromArray($pair['serverId'], $pair['siteId'], JsonApiResource::flattenItem($item)))
                ->values();

            ForgeCache::put(static::cacheKey($pair['serverId'], $pair['siteId']), $deployments, config('forge-insights.cache.deployments', 120));

            return $deployments;
        });

        return $cachedResults->merge($freshResults);
    }

    /**
     * @return Collection<int, DeploymentData>
     */
    protected function fetch(int | string $serverId, int | string $siteId): Collection
    {
        return collect(
            $this->connector->paginate(new ListDeploymentsRequest($serverId, $siteId))
                ->collect()
                ->map(fn (array $item) => DeploymentData::fromArray($serverId, $siteId, JsonApiResource::flattenItem($item)))
                ->all(),
        );
    }

    protected static function cacheKey(int | string $serverId, int | string $siteId): string
    {
        return 'deployments:' . config('forge-insights.organization') . ":{$serverId}:{$siteId}";
    }
}
