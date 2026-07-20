<?php

use Prodstarter\FilamentForgeInsights\Data\SslCertificateData;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListServersRequest;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListSitesRequest;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListSslCertificatesRequest;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SslRepositoryInterface;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    config([
        'forge-insights.token' => 'test-token',
        'forge-insights.organization' => 'acme',
    ]);

    MockClient::destroyGlobal();
});

it('maps the api response into SslCertificateData DTOs for a site', function () {
    MockClient::global([
        ListSslCertificatesRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/ssl-certificates.json'), true),
            200,
        ),
    ]);

    $certificates = app(SslRepositoryInterface::class)->forSite(101, 201);

    expect($certificates)->toHaveCount(1);
    expect($certificates->first())->toBeInstanceOf(SslCertificateData::class);
    expect($certificates->first()->type)->toBe('letsencrypt');
    expect($certificates->first()->isInstalled())->toBeTrue();
    expect($certificates->first()->active)->toBeTrue();
});

it('aggregates certificates across every server and site', function () {
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
        ListSslCertificatesRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/ssl-certificates.json'), true),
            200,
        ),
    ]);

    $certificates = app(SslRepositoryInterface::class)->all();

    expect($certificates)->toHaveCount(1);
});
