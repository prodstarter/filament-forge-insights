<?php

use Prodstarter\FilamentForgeInsights\Data\WorkerData;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListWorkersRequest;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\WorkerRepositoryInterface;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    config([
        'forge-insights.token' => 'test-token',
        'forge-insights.organization' => 'acme',
    ]);

    MockClient::destroyGlobal();
});

it('maps the api response into WorkerData DTOs', function () {
    MockClient::global([
        ListWorkersRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/workers.json'), true),
            200,
        ),
    ]);

    $workers = app(WorkerRepositoryInterface::class)->forServer(101);

    expect($workers)->toHaveCount(1);
    expect($workers->first())->toBeInstanceOf(WorkerData::class);
    expect($workers->first()->command)->toBe('php artisan queue:work');
    expect($workers->first()->processes)->toBe(2);
    expect($workers->first()->isRunning())->toBeTrue();
});
