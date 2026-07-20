<?php

use Prodstarter\FilamentForgeInsights\Data\ScheduledJobData;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListScheduledJobsRequest;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ScheduledJobRepositoryInterface;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    config([
        'forge-insights.token' => 'test-token',
        'forge-insights.organization' => 'acme',
    ]);

    MockClient::destroyGlobal();
});

it('maps the api response into ScheduledJobData DTOs', function () {
    MockClient::global([
        ListScheduledJobsRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/scheduled-jobs.json'), true),
            200,
        ),
    ]);

    $jobs = app(ScheduledJobRepositoryInterface::class)->forServer(101);

    expect($jobs)->toHaveCount(1);
    expect($jobs->first())->toBeInstanceOf(ScheduledJobData::class);
    expect($jobs->first()->name)->toBe('Update Composer');
    expect($jobs->first()->cron)->toBe('45 8 * * *');
    expect($jobs->first()->isActive())->toBeTrue();
});
