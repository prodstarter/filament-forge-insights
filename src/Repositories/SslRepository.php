<?php

namespace Prodstarter\FilamentForgeInsights\Repositories;

use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\SslCertificateData;
use Prodstarter\FilamentForgeInsights\Forge\ForgeConnector;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListSslCertificatesRequest;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SiteRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SslRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Support\ForgeCache;
use Prodstarter\FilamentForgeInsights\Support\JsonApiResource;

class SslRepository implements SslRepositoryInterface
{
    public function __construct(
        protected ForgeConnector $connector,
        protected ServerRepositoryInterface $servers,
        protected SiteRepositoryInterface $sites,
    ) {}

    public function forSite(int | string $serverId, int | string $siteId): Collection
    {
        return ForgeCache::remember(
            'ssl:' . config('forge-insights.organization') . ":{$serverId}:{$siteId}",
            config('forge-insights.cache.ssl', 1800),
            fn () => collect(
                $this->connector->paginate(new ListSslCertificatesRequest($serverId, $siteId))
                    ->collect()
                    ->map(fn (array $item) => SslCertificateData::fromArray($serverId, $siteId, JsonApiResource::flattenItem($item)))
                    ->all(),
            ),
        );
    }

    public function all(): Collection
    {
        return ForgeCache::remember(
            'ssl:all:' . config('forge-insights.organization'),
            config('forge-insights.cache.ssl', 1800),
            function () {
                $certificates = collect();

                foreach ($this->servers->all() as $server) {
                    foreach ($this->sites->all($server->id) as $site) {
                        $certificates = $certificates->merge($this->forSite($server->id, $site->id));
                    }
                }

                return $certificates->values();
            },
        );
    }
}
