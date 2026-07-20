<?php

namespace Prodstarter\FilamentForgeInsights\Forge;

use Prodstarter\FilamentForgeInsights\Exceptions\ForgeNotConfiguredException;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;

/**
 * For account-level Forge endpoints (e.g. /user, /orgs) that aren't scoped
 * to a single organization, used by the Settings page to validate a token
 * and list organizations before the user has saved/selected one.
 */
class ForgeAccountConnector extends Connector
{
    public function __construct(
        protected readonly string $token,
    ) {}

    public function resolveBaseUrl(): string
    {
        return rtrim(config('forge-insights.base_url'), '/');
    }

    protected function defaultAuth(): Authenticator
    {
        if (blank($this->token)) {
            throw ForgeNotConfiguredException::missingToken();
        }

        return new TokenAuthenticator($this->token);
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }
}
