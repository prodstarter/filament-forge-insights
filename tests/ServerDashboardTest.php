<?php

use Prodstarter\FilamentForgeInsights\Filament\Pages\ServerDashboard;
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
});

it('shows a disconnected state with a link to settings', function () {
    livewire(ServerDashboard::class)
        ->assertSuccessful()
        ->assertSeeText('Not connected to Forge')
        ->assertSeeText('Go to Settings');
});

it('shows the organization-wide overview when nothing is scoped', function () {
    ForgeInsightsSetting::query()->create([
        'token' => 'test-token',
        'organization' => 'acme',
    ]);

    app(SettingsManager::class)->applyToConfig();

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
    ]);

    livewire(ServerDashboard::class)
        ->assertSuccessful()
        ->assertSeeText('Infrastructure Overview')
        ->assertSeeText('Servers')
        ->assertSeeText('app-production')
        ->assertSeeText('Recent Deployments')
        ->assertSeeText('Update dependencies');
});

it('shows the full site-scoped dashboard when scoped to a site', function () {
    ForgeInsightsSetting::query()->create([
        'token' => 'test-token',
        'organization' => 'acme',
        'server' => '101',
        'site' => '201',
    ]);

    app(SettingsManager::class)->applyToConfig();

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

    livewire(ServerDashboard::class)
        ->assertSuccessful()
        ->assertSeeText('example.com')
        ->assertSeeText('Server Overview')
        ->assertSeeText('Site Information')
        ->assertSeeText('app-production')
        ->assertSeeText('DigitalOcean')
        ->assertSeeText('Recent Deployments')
        ->assertSeeText('Update dependencies')
        ->assertDontSeeText('203.0.113.10')
        ->call('toggleIpVisibility')
        ->assertSeeText('203.0.113.10');
});

it('surfaces an attention banner when the latest deployment failed or the ssl certificate is expiring soon', function () {
    ForgeInsightsSetting::query()->create([
        'token' => 'test-token',
        'organization' => 'acme',
        'server' => '101',
        'site' => '201',
    ]);

    app(SettingsManager::class)->applyToConfig();

    $deployments = json_decode(file_get_contents(__DIR__ . '/Fixtures/deployments.json'), true);
    $deployments['data'][] = [
        'id' => '303',
        'type' => 'deployments',
        'attributes' => [
            'commit' => [
                'hash' => 'ffffffffffffff',
                'author' => 'Jane Doe',
                'message' => 'Broken deploy',
                'branch' => 'main',
            ],
            'type' => 'Manual',
            'status' => 'failed',
            'started_at' => '2026-07-21T09:00:00.000000Z',
            'ended_at' => '2026-07-21T09:00:04.000000Z',
            'created_at' => '2026-07-21T09:00:00.000000Z',
            'updated_at' => '2026-07-21T09:00:04.000000Z',
        ],
    ];

    $ssl = json_decode(file_get_contents(__DIR__ . '/Fixtures/ssl-certificates.json'), true);
    $ssl['data'][0]['attributes']['updated_at'] = now()->subDays(85)->toIso8601String();

    MockClient::global([
        ListServersRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/servers.json'), true),
            200,
        ),
        ListSitesRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/sites.json'), true),
            200,
        ),
        ListDeploymentsRequest::class => MockResponse::make($deployments, 200),
        ListSslCertificatesRequest::class => MockResponse::make($ssl, 200),
    ]);

    livewire(ServerDashboard::class)
        ->assertSuccessful()
        ->assertSeeText('need attention')
        ->assertSeeText('last deployment failed')
        ->assertSeeText('expires in')
        ->assertDontSeeText('Everything looks good');
});
