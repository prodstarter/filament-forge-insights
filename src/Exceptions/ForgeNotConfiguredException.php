<?php

namespace Prodstarter\FilamentForgeInsights\Exceptions;

use RuntimeException;

class ForgeNotConfiguredException extends RuntimeException
{
    public static function missingToken(): self
    {
        return new self('No Forge API token has been configured. Set FORGE_API_TOKEN or connect an account via the Forge Insights settings page.');
    }

    public static function missingOrganization(): self
    {
        return new self('No Forge organization has been configured. Set FORGE_ORGANIZATION_ID or select an organization via the Forge Insights settings page.');
    }
}
