<?php

use Prodstarter\FilamentForgeInsights\Filament\Pages\ListDeployments;
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
    livewire(ListDeployments::class)->assertSuccessful();
});

it('renders recent deployments across every server and site when connected', function () {
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

    livewire(ListDeployments::class)
        ->assertSuccessful()
        ->assertSeeText('example.com')
        ->assertSeeText('Update dependencies');
});
