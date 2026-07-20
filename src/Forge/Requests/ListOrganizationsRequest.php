<?php

namespace Prodstarter\FilamentForgeInsights\Forge\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class ListOrganizationsRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/orgs';
    }
}
