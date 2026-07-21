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
            'sites:' . config('forge-insights.organization') . ":{$serverId}",
            config('forge-insights.cache.sites', 600),
            fn () => collect(
                $this->connector->paginate(new ListSitesRequest($serverId))
                    ->collect()
                    ->map(fn (array $item) => SiteData::fromArray($serverId, JsonApiResource::flattenItem($item)))
                    ->all(),
            ),
        );

        $scopedSiteId = config('forge-insights.site');

        if (blank($scopedSiteId)) {
            return $sites;
        }

        return $sites
            ->filter(fn (SiteData $site) => (string) $site->id === (string) $scopedSiteId)
            ->values();
    }

    public function find(int | string $serverId, int | string $id): ?SiteData
    {
        return $this->all($serverId)->first(fn (SiteData $site) => (string) $site->id === (string) $id);
    }
}
