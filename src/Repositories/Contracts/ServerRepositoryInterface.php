<?php

namespace Prodstarter\FilamentForgeInsights\Repositories\Contracts;

use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\ServerData;

interface ServerRepositoryInterface
{
    /**
     * @return Collection<int, ServerData>
     */
    public function all(): Collection;

    public function find(int | string $id): ?ServerData;
}
