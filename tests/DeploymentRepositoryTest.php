<?php

use Prodstarter\FilamentForgeInsights\Data\DeploymentData;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListDeploymentsRequest;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListServersRequest;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListSitesRequest;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\DeploymentRepositoryInterface;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    config([
        'forge-insights.token' => 'test-token',
        'forge-insights.organization' => 'acme',
    ]);

    MockClient::destroyGlobal();
});

it('maps the api response into DeploymentData DTOs for a site', function () {
    MockClient::global([
        ListDeploymentsRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/deployments.json'), true),
            200,
        ),
    ]);

    $deployments = app(DeploymentRepositoryInterface::class)->forSite(101, 201);

    expect($deployments)->toHaveCount(2);
    expect($deployments->first())->toBeInstanceOf(DeploymentData::class);
    expect($deployments->first()->commitMessage)->toBe('Fix checkout bug');
    expect($deployments->first()->triggeredBy)->toBe('Push to deploy');
    expect($deployments->first()->isFinished())->toBeTrue();
    expect($deployments->first()->durationInSeconds())->toBe(42);
});

it('aggregates the most recent deployments across every server and site, newest first', function () {
    MockClient::global([
        ListServersRequest::class => MockResponse::make([
            'data' => [[
                'id' => '101',
                'type' => 'servers',
                'attributes' => ['id' => 101, 'name' => 'app-production'],
            ]],
            'meta' => ['next_cursor' => null],
        ], 200),
        ListSitesRequest::class => MockResponse::make([
            'data' => [[
                'id' => '201',
                'type' => 'sites',
                'attributes' => ['name' => 'example.com'],
            ]],
            'meta' => ['next_cursor' => null],
        ], 200),
        ListDeploymentsRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/deployments.json'), true),
            200,
        ),
    ]);

    $recent = app(DeploymentRepositoryInterface::class)->recent(3);

    expect($recent)->toHaveCount(2);
    expect($recent->first()->commitMessage)->toBe('Update dependencies');
    expect($recent->last()->commitMessage)->toBe('Fix checkout bug');
});
