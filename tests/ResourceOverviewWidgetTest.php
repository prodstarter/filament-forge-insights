<?php

use Prodstarter\FilamentForgeInsights\Filament\Widgets\ResourceOverviewWidget;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListDeploymentsRequest;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListServersRequest;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListSitesRequest;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListSslCertificatesRequest;
use Prodstarter\FilamentForgeInsights\Settings\ForgeInsightsSetting;
use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Workbench\App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    MockClient::destroyGlobal();

    MockClient::global([
        ListServersRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/servers.json'), true),
            200,
        ),
        ListSitesRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/sites.json'), true),
            200,
        ),
        ListDeploymentsRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/deployments.json'), true),
            200,
        ),
        ListSslCertificatesRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/ssl-certificates.json'), true),
            200,
        ),
    ]);
});

it('shows organization-wide stats when nothing is scoped', function () {
    ForgeInsightsSetting::query()->create([
        'token' => 'test-token',
        'organization' => 'acme',
    ]);

    app(SettingsManager::class)->applyToConfig();

    livewire(ResourceOverviewWidget::class)
        ->assertSuccessful()
        ->assertSeeText('Servers')
        ->assertSeeText('Websites');
});

it('shows a single server\'s stats when scoped to a server', function () {
    ForgeInsightsSetting::query()->create([
        'token' => 'test-token',
        'organization' => 'acme',
        'server' => '101',
    ]);

    app(SettingsManager::class)->applyToConfig();

    livewire(ResourceOverviewWidget::class)
        ->assertSuccessful()
        ->assertSeeText('app-production')
        ->assertSeeText('Sites');
});

it('shows a single site\'s stats when scoped to a site', function () {
    ForgeInsightsSetting::query()->create([
        'token' => 'test-token',
        'organization' => 'acme',
        'server' => '101',
        'site' => '201',
    ]);

    app(SettingsManager::class)->applyToConfig();

    livewire(ResourceOverviewWidget::class)
        ->assertSuccessful()
        ->assertSeeText('example.com')
        ->assertSeeText('Hosting')
        ->assertSeeText('SSL');
});
