<?php

namespace Prodstarter\FilamentForgeInsights\Forge\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\PaginationPlugin\Contracts\Paginatable;

class ListWorkersRequest extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(
        protected readonly int | string $serverId,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/servers/{$this->serverId}/background-processes";
    }
}
