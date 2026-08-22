<?php

use Prodstarter\FilamentForgeInsights\Filament\Pages\ListSites;
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
    livewire(ListSites::class)->assertSuccessful();
});

it('renders sites across every server when connected', function () {
    ForgeInsightsSetting::query()->create([
        'token' => 'test-token',
        'organization' => 'acme',
    ]);

    app(SettingsManager::class)->applyToConfig();

    MockClient::global([
        ListServersRequest::class => MockResponse::make([
            'data' => [[
                'id' => '101',
                'type' => 'servers',
                'attributes' => ['id' => 101, 'name' => 'app-production'],
            ]],
            'meta' => ['next_cursor' => null],
        ], 200),
        ListSitesRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/sites.json'), true),
            200,
        ),
    ]);

    livewire(ListSites::class)
        ->assertSuccessful()
        ->assertSeeText('example.com')
        ->assertSeeText('app-production');
});

it('is not accessible when scoped down to a single site', function () {
    ForgeInsightsSetting::query()->create([
        'token' => 'test-token',
        'organization' => 'acme',
        'server' => '101',
        'site' => '201',
    ]);

    app(SettingsManager::class)->applyToConfig();

    expect(ListSites::canAccess())->toBeFalse();
    expect(ListSites::shouldRegisterNavigation())->toBeFalse();
});
