<?php

namespace Prodstarter\FilamentForgeInsights\Forge;

use Illuminate\Support\Facades\Cache;
use Prodstarter\FilamentForgeInsights\Exceptions\ForgeNotConfiguredException;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\HasPagination;
use Saloon\PaginationPlugin\CursorPaginator;
use Saloon\PaginationPlugin\Paginator;
use Saloon\RateLimitPlugin\Contracts\RateLimitStore;
use Saloon\RateLimitPlugin\Limit;
use Saloon\RateLimitPlugin\Stores\LaravelCacheStore;
use Saloon\RateLimitPlugin\Traits\HasRateLimits;
use Saloon\Traits\Plugins\HasTimeout;
use Throwable;

class ForgeConnector extends Connector implements HasPagination
{
    use HasRateLimits;
    use HasTimeout;

    protected float $connectTimeout = 5;

    protected float $requestTimeout = 15;

    /**
     * Retry transient failures (network blips, momentary 5xx) a couple of
     * times with a short exponential backoff before giving up.
     */
    public ?int $tries = 3;

    public ?int $retryInterval = 200;

    public ?bool $useExponentialBackoff = true;

    /**
     * Pass explicit $token/$organization to test unsaved credentials (e.g. from
     * the Settings page) without mutating global config. Leave both null to
     * read the currently configured connection, which is what repositories do.
     */
    public function __construct(
        protected readonly ?string $token = null,
        protected readonly int | string | null $organization = null,
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
     * No proactive local limit here — only Saloon's automatic 429 detector
     * (always active via HasRateLimits, regardless of what this returns)
     * protects against Forge's real limit. A hand-tracked local counter
     * sounds like it should be strictly safer, but it isn't a clean win in
     * practice: every request pays for a cache read + write per configured
     * limit (roughly half of all cache activity on a cold page load was
     * this bookkeeping, measured directly), and it still doesn't fully
     * prevent real 429s — concurrent pooled requests can all pass a "still
     * under budget" check before any of them records its own usage, so a
     * burst can clear the local check and still trip Forge's actual limit.
     * Given it doesn't reliably prevent the failure mode it exists for, but
     * reliably costs overhead on every request, reacting to a real 429 when
     * it happens is the better trade here.
     *
     * @return array<int, Limit>
     */
    protected function resolveLimits(): array
    {
        return [];
    }

    protected function resolveRateLimitStore(): RateLimitStore
    {
        return new LaravelCacheStore(Cache::store());
    }

    /**
     * Saloon's default prefix is just the connector's class name, so every
     * token/organization sharing this connector shares one rate limit
     * bucket — switching Forge accounts inherits whatever throttling the
     * previous one triggered. Scoping the prefix per token keeps each
     * connected account's limit independent of the others.
     */
    protected function getLimiterPrefix(): ?string
    {
        $token = $this->token ?? config('forge-insights.token');

        return 'ForgeConnector:' . substr(hash('sha256', (string) $token), 0, 12);
    }

    /**
     * Send several single-page requests concurrently instead of one after
     * another. Rate limiting and authentication apply exactly as they do
     * for send() — only wall-clock time changes, since the underlying HTTP
     * calls overlap instead of waiting for each other to finish. Only
     * meaningful for requests you already know are a single page each;
     * this does not paginate, it just fans out.
     *
     * @param  array<int|string, Request>  $requests
     * @return array<int|string, Response>
     *
     * @throws Throwable if any request in the pool failed — the first
     *                   exception encountered, matching what a sequential
     *                   loop of send() calls would have thrown on its
     *                   first failure.
     */
    public function poolRequests(array $requests, int $concurrency = 10): array
    {
        if ($requests === []) {
            return [];
        }

        $responses = [];
        $exceptions = [];

        $this->pool($requests, $concurrency)
            ->withResponseHandler(function (Response $response, int | string $key) use (&$responses): void {
                $responses[$key] = $response;
            })
            ->withExceptionHandler(function (Throwable $exception, int | string $key) use (&$exceptions): void {
                $exceptions[$key] = $exception;
            })
            ->send()
            ->wait();

        if ($exceptions !== []) {
            throw reset($exceptions);
        }

        return $responses;
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
