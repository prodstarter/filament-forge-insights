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

    public function find(int | string $serverId, int | string $id): ?SiteData;
}
