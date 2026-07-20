<?php

namespace Prodstarter\FilamentForgeInsights\Repositories\Contracts;

use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\SslCertificateData;

interface SslRepositoryInterface
{
    /**
     * @return Collection<int, SslCertificateData>
     */
    public function forSite(int | string $serverId, int | string $siteId): Collection;

    /**
     * Every certificate across every server/site.
     *
     * @return Collection<int, SslCertificateData>
     */
    public function all(): Collection;
}
