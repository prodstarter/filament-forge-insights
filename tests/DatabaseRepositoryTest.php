<?php

use Prodstarter\FilamentForgeInsights\Data\DatabaseData;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListDatabasesRequest;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\DatabaseRepositoryInterface;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    config([
        'forge-insights.token' => 'test-token',
        'forge-insights.organization' => 'acme',
    ]);

    MockClient::destroyGlobal();
});

it('maps the api response into DatabaseData DTOs', function () {
    MockClient::global([
        ListDatabasesRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/databases.json'), true),
            200,
        ),
    ]);

    $databases = app(DatabaseRepositoryInterface::class)->forServer(101);

    expect($databases)->toHaveCount(1);
    expect($databases->first())->toBeInstanceOf(DatabaseData::class);
    expect($databases->first()->name)->toBe('forge');
    expect($databases->first()->status)->toBe('installed');
});
