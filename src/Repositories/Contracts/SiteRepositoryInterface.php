<?php

namespace Prodstarter\FilamentForgeInsights\Repositories\Contracts;

use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\SiteData;

interface SiteRepositoryInterface
{
    /**
     * @return Collection<int, SiteData>
     */
    public function all(int | string $serverId): Collection;

    /**
     * Fetch sites for several servers at once. Prefer this over calling
     * all() in a loop when you already know every server ID up front — it
     * fetches uncached servers concurrently instead of one at a time.
     *
     * @param  Collection<int, int|string>  $serverIds
     * @return Collection<int|string, Collection<int, SiteData>>
     */
    public function allForServers(Collection $serverIds): Collection;

    public function find(int | string $serverId, int | string $id): ?SiteData;
}
