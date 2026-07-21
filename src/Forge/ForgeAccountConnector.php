<?php

namespace Prodstarter\FilamentForgeInsights\Forge;

use Prodstarter\FilamentForgeInsights\Exceptions\ForgeNotConfiguredException;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Saloon\Traits\Plugins\HasTimeout;

/**
 * For account-level Forge endpoints (e.g. /user, /orgs) that aren't scoped
 * to a single organization, used by the Settings page to validate a token
 * and list organizations before the user has saved/selected one.
 */
class ForgeAccountConnector extends Connector
{
    use HasTimeout;

    protected float $connectTimeout = 5;

    protected float $requestTimeout = 15;

    public ?int $tries = 3;

    public ?int $retryInterval = 200;

    public ?bool $useExponentialBackoff = true;

    public function __construct(
        protected readonly string $token,
    ) {}

    /**
     * Always throw on a failed response, so callers can rely on catching an
     * exception instead of remembering to check `successful()` themselves.
     */
    public function boot(PendingRequest $pendingRequest): void
    {
        $pendingRequest->middleware()->onResponse(fn (Response $response): Response => $response->throw());
    }

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
