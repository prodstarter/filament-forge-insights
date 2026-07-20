<?php

namespace Prodstarter\FilamentForgeInsights\Repositories\Contracts;

use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\DatabaseData;

interface DatabaseRepositoryInterface
{
    /**
     * @return Collection<int, DatabaseData>
     */
    public function forServer(int | string $serverId): Collection;

    /**
     * Every database across every server.
     *
     * @return Collection<int, DatabaseData>
     */
    public function all(): Collection;
}
