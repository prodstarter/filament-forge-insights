<?php

use Prodstarter\FilamentForgeInsights\Filament\Pages\ListServers;
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
    livewire(ListServers::class)->assertSuccessful();
});

it('renders servers when connected', function () {
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
    ]);

    livewire(ListServers::class)
        ->assertSuccessful()
        ->assertSeeText('app-production')
        ->assertSeeText('app-staging');
});
