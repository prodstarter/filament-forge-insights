<?php

use Prodstarter\FilamentForgeInsights\Filament\Pages\InfrastructureDashboard;
use Prodstarter\FilamentForgeInsights\Filament\Widgets\RecentDeploymentsWidget;
use Prodstarter\FilamentForgeInsights\Filament\Widgets\ResourceOverviewWidget;
use Prodstarter\FilamentForgeInsights\Filament\Widgets\ServerHealthTableWidget;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListDeploymentsRequest;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListServersRequest;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListSitesRequest;
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

it('renders when not connected', function () {
    livewire(InfrastructureDashboard::class)->assertSuccessful();

    livewire(ResourceOverviewWidget::class)->assertSuccessful();
    livewire(ServerHealthTableWidget::class)->assertSuccessful();
    livewire(RecentDeploymentsWidget::class)->assertSuccessful();
});

it('renders the widgets with real data when connected', function () {
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

    livewire(InfrastructureDashboard::class)->assertSuccessful();

    livewire(ResourceOverviewWidget::class)
        ->assertSuccessful()
        ->assertSeeText('Connected')
        ->assertSeeText('Deployments today');

    livewire(ServerHealthTableWidget::class)
        ->assertSuccessful()
        ->assertSeeText('app-production')
        ->assertSeeText('app-staging');

    livewire(RecentDeploymentsWidget::class)
        ->assertSuccessful()
        ->assertSeeText('Update dependencies');
});
