<?php

use Prodstarter\FilamentForgeInsights\Data\SiteData;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListSitesRequest;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SiteRepositoryInterface;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    config([
        'forge-insights.token' => 'test-token',
        'forge-insights.organization' => 'acme',
    ]);

    MockClient::destroyGlobal();
});

it('maps the api response into SiteData DTOs', function () {
    MockClient::global([
        ListSitesRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/sites.json'), true),
            200,
        ),
    ]);

    $sites = app(SiteRepositoryInterface::class)->all(101);

    expect($sites)->toHaveCount(2);
    expect($sites->first())->toBeInstanceOf(SiteData::class);
    expect($sites->first()->domain)->toBe('example.com');
    expect($sites->first()->serverId)->toBe(101);
    expect($sites->first()->quickDeploy)->toBeTrue();
    expect($sites->first()->isSecured)->toBeTrue();
    expect($sites->first()->repositoryProvider)->toBe('GitHub');
    expect($sites->first()->repositoryBranch)->toBe('main');
    expect($sites->last()->deploymentStatus)->toBe('deploying');
});

it('narrows the site list to the scoped site when one is configured', function () {
    config(['forge-insights.site' => 202]);

    MockClient::global([
        ListSitesRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/sites.json'), true),
            200,
        ),
    ]);

    $sites = app(SiteRepositoryInterface::class)->all(101);

    expect($sites)->toHaveCount(1);
    expect($sites->first()->domain)->toBe('beta.example.com');
});

it('caches the site list per server so a second call does not re-hit the api', function () {
    $mockClient = MockClient::global([
        ListSitesRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/sites.json'), true),
            200,
        ),
    ]);

    $repository = app(SiteRepositoryInterface::class);

    $repository->all(101);
    $repository->all(101);

    $mockClient->assertSentCount(1);
});
