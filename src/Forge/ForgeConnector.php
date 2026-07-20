<?php

namespace Prodstarter\FilamentForgeInsights\Forge;

use Illuminate\Support\Facades\Cache;
use Prodstarter\FilamentForgeInsights\Exceptions\ForgeNotConfiguredException;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\HasPagination;
use Saloon\PaginationPlugin\CursorPaginator;
use Saloon\PaginationPlugin\Paginator;
use Saloon\RateLimitPlugin\Contracts\RateLimitStore;
use Saloon\RateLimitPlugin\Limit;
use Saloon\RateLimitPlugin\Stores\LaravelCacheStore;
use Saloon\RateLimitPlugin\Traits\HasRateLimits;

class ForgeConnector extends Connector implements HasPagination
{
    use HasRateLimits;

    /**
     * Pass explicit $token/$organization to test unsaved credentials (e.g. from
     * the Settings page) without mutating global config. Leave both null to
     * read the currently configured connection, which is what repositories do.
     */
    public function __construct(
        protected readonly ?string $token = null,
        protected readonly int | string | null $organization = null,
    ) {}

    public function resolveBaseUrl(): string
    {
        $organization = $this->organization ?? config('forge-insights.organization');

        if (blank($organization)) {
            throw ForgeNotConfiguredException::missingOrganization();
        }

        return rtrim(config('forge-insights.base_url'), '/') . '/orgs/' . $organization;
    }

    protected function defaultAuth(): Authenticator
    {
        $token = $this->token ?? config('forge-insights.token');

        if (blank($token)) {
            throw ForgeNotConfiguredException::missingToken();
        }

        return new TokenAuthenticator($token);
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

    /**
     * @return array<int, Limit>
     */
    protected function resolveLimits(): array
    {
        return [
            Limit::allow(120)->everyMinute(),
        ];
    }

    protected function resolveRateLimitStore(): RateLimitStore
    {
        return new LaravelCacheStore(Cache::store());
    }

    public function paginate(Request $request): Paginator
    {
        return new class($this, $request) extends CursorPaginator
        {
            protected function getNextCursor(Response $response): int | string
            {
                return $response->json('meta.next_cursor');
            }

            protected function isLastPage(Response $response): bool
            {
                return blank($response->json('meta.next_cursor'));
            }

            /**
             * @return array<int, array<string, mixed>>
             */
            protected function getPageItems(Response $response, Request $request): array
            {
                return $response->json('data') ?? [];
            }

            /**
             * Forge expects the cursor as a JSON:API-style `page[cursor]` query
             * parameter, not the plain `cursor` the base class defaults to.
             */
            protected function applyPagination(Request $request): Request
            {
                if ($this->currentResponse instanceof Response) {
                    $request->query()->add('page[cursor]', $this->getNextCursor($this->currentResponse));
                }

                return $request;
            }
        };
    }
}
