<?php

namespace Prodstarter\FilamentForgeInsights\Repositories\Contracts;

use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\DeploymentData;

interface DeploymentRepositoryInterface
{
    /**
     * @return Collection<int, DeploymentData>
     */
    public function forSite(int | string $serverId, int | string $siteId): Collection;

    /**
     * The most recent deployments across every server/site, newest first.
     *
     * @return Collection<int, DeploymentData>
     */
    public function recent(int $limit = 10): Collection;
}
