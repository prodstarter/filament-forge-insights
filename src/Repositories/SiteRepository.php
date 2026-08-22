<?php

namespace Prodstarter\FilamentForgeInsights\Repositories;

use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\SiteData;
use Prodstarter\FilamentForgeInsights\Forge\ForgeConnector;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListSitesRequest;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SiteRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Support\ForgeCache;
use Prodstarter\FilamentForgeInsights\Support\JsonApiResource;

class SiteRepository implements SiteRepositoryInterface
{
    public function __construct(
        protected ForgeConnector $connector,
    ) {}

    public function all(int | string $serverId): Collection
    {
        $sites = ForgeCache::remember(
            static::cacheKey($serverId),
            config('forge-insights.cache.sites', 600),
            fn () => $this->fetch($serverId),
        );

        return $this->scopeToSite($sites);
    }

    /**
     * Fetch sites for several servers at once, sending one real request per
     * *uncached* server concurrently instead of looping through them one at
     * a time. Servers already warm in cache are simply read, not re-fetched.
     *
     * Only the first page of each server's sites is fetched concurrently —
     * Forge's servers realistically host a handful of sites each, so this
     * covers the common case. A server with more than one page of sites
     * (rare) falls back to the normal sequential, fully-paginated all().
     *
     * @param  Collection<int, int|string>  $serverIds
     * @return Collection<int|string, Collection<int, SiteData>>
     */
    public function allForServers(Collection $serverIds): Collection
    {
        /** @var Collection<int, array{serverId: int|string, cached: ?Collection<int, SiteData>}> $lookups */
        $lookups = $serverIds->map(fn (int | string $serverId) => [
            'serverId' => $serverId,
            'cached' => ForgeCache::get(static::cacheKey($serverId)),
        ]);

        $results = $lookups
            ->reject(fn (array $lookup) => is_null($lookup['cached']))
            ->mapWithKeys(fn (array $lookup) => [$lookup['serverId'] => $this->scopeToSite($lookup['cached'])]);

        $uncachedServerIds = $lookups
            ->filter(fn (array $lookup) => is_null($lookup['cached']))
            ->map(fn (array $lookup) => $lookup['serverId'])
            ->values();

        if ($uncachedServerIds->isNotEmpty()) {
            $responses = $this->connector->poolRequests(
                $uncachedServerIds->mapWithKeys(fn (int | string $serverId) => [$serverId => new ListSitesRequest($serverId)])->all(),
            );

            $fresh = collect($responses)->mapWithKeys(function ($response, int | string $serverId) {
                if (filled($response->json('meta.next_cursor'))) {
                    // More than one page — let all() paginate it properly instead of guessing.
                    return [$serverId => $this->all($serverId)];
                }

                $sites = collect($response->json('data') ?? [])
                    ->map(fn (array $item) => SiteData::fromArray($serverId, JsonApiResource::flattenItem($item)))
                    ->values();

                ForgeCache::put(static::cacheKey($serverId), $sites, config('forge-insights.cache.sites', 600));

                return [$serverId => $this->scopeToSite($sites)];
            });

            // union(), not merge(): merge() re-indexes integer keys like
            // array_merge() does, which would silently drop these results
            // (server IDs are integers) instead of keying by server ID.
            $results = $results->union($fresh);
        }

        return $serverIds->mapWithKeys(fn (int | string $serverId) => [$serverId => $results->get($serverId) ?? collect()]);
    }

    public function find(int | string $serverId, int | string $id): ?SiteData
    {
        return $this->all($serverId)->first(fn (SiteData $site) => (string) $site->id === (string) $id);
    }

    /**
     * @return Collection<int, SiteData>
     */
    protected function fetch(int | string $serverId): Collection
    {
        return collect(
            $this->connector->paginate(new ListSitesRequest($serverId))
                ->collect()
                ->map(fn (array $item) => SiteData::fromArray($serverId, JsonApiResource::flattenItem($item)))
                ->all(),
        );
    }

    /**
     * @param  Collection<int, SiteData>  $sites
     * @return Collection<int, SiteData>
     */
    protected function scopeToSite(Collection $sites): Collection
    {
        $scopedSiteId = config('forge-insights.site');

        if (blank($scopedSiteId)) {
            return $sites;
        }

        return $sites
            ->filter(fn (SiteData $site) => (string) $site->id === (string) $scopedSiteId)
            ->values();
    }

    protected static function cacheKey(int | string $serverId): string
    {
        return 'sites:' . config('forge-insights.organization') . ":{$serverId}";
    }
}
