<?php

use Prodstarter\FilamentForgeInsights\Data\ServerData;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListServersRequest;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    config([
        'forge-insights.token' => 'test-token',
        'forge-insights.organization' => 'acme',
    ]);

    MockClient::destroyGlobal();
});

it('maps the api response into ServerData DTOs', function () {
    MockClient::global([
        ListServersRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/servers.json'), true),
            200,
        ),
    ]);

    $servers = app(ServerRepositoryInterface::class)->all();

    expect($servers)->toHaveCount(2);
    expect($servers->first())->toBeInstanceOf(ServerData::class);
    expect($servers->first()->name)->toBe('app-production');
    expect($servers->first()->ipAddress)->toBe('203.0.113.10');
    expect($servers->first()->isReady)->toBeTrue();
    expect($servers->first()->isOnline())->toBeTrue();
    expect($servers->first()->createdAt?->toDateString())->toBe('2026-01-15');
});

it('finds a single server by id', function () {
    MockClient::global([
        ListServersRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/servers.json'), true),
            200,
        ),
    ]);

    $server = app(ServerRepositoryInterface::class)->find(102);

    expect($server)->not->toBeNull();
    expect($server->name)->toBe('app-staging');
});

it('narrows the server list to the scoped server when one is configured', function () {
    config(['forge-insights.server' => 102]);

    MockClient::global([
        ListServersRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/servers.json'), true),
            200,
        ),
    ]);

    $servers = app(ServerRepositoryInterface::class)->all();

    expect($servers)->toHaveCount(1);
    expect($servers->first()->name)->toBe('app-staging');
});

it('caches the server list so a second call does not re-hit the api', function () {
    $mockClient = MockClient::global([
        ListServersRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/servers.json'), true),
            200,
        ),
    ]);

    $repository = app(ServerRepositoryInterface::class);

    $repository->all();
    $repository->all();

    $mockClient->assertSentCount(1);
});
