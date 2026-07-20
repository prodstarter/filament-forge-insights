<?php

namespace Prodstarter\FilamentForgeInsights\Repositories\Contracts;

use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\ScheduledJobData;

interface ScheduledJobRepositoryInterface
{
    /**
     * @return Collection<int, ScheduledJobData>
     */
    public function forServer(int | string $serverId): Collection;

    /**
     * Every scheduled job across every server.
     *
     * @return Collection<int, ScheduledJobData>
     */
    public function all(): Collection;
}
