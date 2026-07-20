<?php

use Prodstarter\FilamentForgeInsights\Exceptions\ForgeNotConfiguredException;
use Prodstarter\FilamentForgeInsights\Forge\ForgeConnector;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListServersRequest;
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
