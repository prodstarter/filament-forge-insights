<?php

namespace Prodstarter\FilamentForgeInsights\Repositories\Contracts;

use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\WorkerData;

interface WorkerRepositoryInterface
{
    /**
     * @return Collection<int, WorkerData>
     */
    public function forServer(int | string $serverId): Collection;

    /**
     * Every worker across every server.
     *
     * @return Collection<int, WorkerData>
     */
    public function all(): Collection;
}
