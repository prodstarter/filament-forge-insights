<?php

use Prodstarter\FilamentForgeInsights\Exceptions\ForgeNotConfiguredException;
use Prodstarter\FilamentForgeInsights\Forge\ForgeConnector;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListServersRequest;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListSitesRequest;
use Saloon\Exceptions\Request\ServerException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    config([
        'forge-insights.token' => 'test-token',
        'forge-insights.organization' => 'acme',
    ]);

    MockClient::destroyGlobal();
});

it('sends a bearer-authenticated request to the org-scoped base url', function () {
    $mockClient = MockClient::global([
        ListServersRequest::class => MockResponse::make(['data' => [], 'meta' => ['next_cursor' => null]], 200),
    ]);

    app(ForgeConnector::class)->send(new ListServersRequest);

    $mockClient->assertSent(function ($request, $response) {
        $pending = $response->getPendingRequest();

        return $pending->getUrl() === 'https://forge.laravel.com/api/orgs/acme/servers'
            && $pending->headers()->get('Authorization') === 'Bearer test-token'
            && $pending->headers()->get('Accept') === 'application/json';
    });
});

it('throws when no token is configured', function () {
    config(['forge-insights.token' => null]);

    app(ForgeConnector::class)->send(new ListServersRequest);
})->throws(ForgeNotConfiguredException::class);

it('throws when no organization is configured', function () {
    config(['forge-insights.organization' => null]);

    app(ForgeConnector::class)->send(new ListServersRequest);
})->throws(ForgeNotConfiguredException::class);

it('scopes the rate limit prefix to the token, so switching Forge accounts does not inherit another account\'s throttled state', function () {
    $reflect = fn (ForgeConnector $connector) => (new ReflectionMethod($connector, 'getLimiterPrefix'))->invoke($connector);

    $connectorA = new ForgeConnector('token-a', 'acme');
    $connectorB = new ForgeConnector('token-b', 'acme');
    $connectorASecondInstance = new ForgeConnector('token-a', 'other-org');

    expect($reflect($connectorA))
        ->not->toBe($reflect($connectorB))
        ->toBe($reflect($connectorASecondInstance));
});

it('sends pooled requests concurrently and returns responses keyed the same way they were given', function () {
    MockClient::global([
        '*/servers/101/sites' => MockResponse::make(['data' => [['id' => '1', 'attributes' => ['name' => 'site-101']]], 'meta' => ['next_cursor' => null]]),
        '*/servers/102/sites' => MockResponse::make(['data' => [['id' => '2', 'attributes' => ['name' => 'site-102']]], 'meta' => ['next_cursor' => null]]),
    ]);

    $responses = app(ForgeConnector::class)->poolRequests([
        101 => new ListSitesRequest(101),
        102 => new ListSitesRequest(102),
    ]);

    expect($responses)->toHaveKeys([101, 102]);
    expect($responses[101]->json('data.0.attributes.name'))->toBe('site-101');
    expect($responses[102]->json('data.0.attributes.name'))->toBe('site-102');
});

it('throws when any request in the pool fails', function () {
    MockClient::global([
        '*/servers/101/sites' => MockResponse::make(['data' => [], 'meta' => ['next_cursor' => null]], 200),
        '*/servers/102/sites' => MockResponse::make(['message' => 'Server error'], 500),
    ]);

    app(ForgeConnector::class)->poolRequests([
        101 => new ListSitesRequest(101),
        102 => new ListSitesRequest(102),
    ]);
})->throws(ServerException::class);

it('returns an empty array immediately for an empty pool', function () {
    expect(app(ForgeConnector::class)->poolRequests([]))->toBe([]);
});
