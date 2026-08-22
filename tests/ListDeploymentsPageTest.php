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

it('paginates the deployment history instead of dumping every record onto one page', function () {
    ForgeInsightsSetting::query()->create([
        'token' => 'test-token',
        'organization' => 'acme',
    ]);

    app(SettingsManager::class)->applyToConfig();

    $labels = range('A', 'O');

    $deployments = collect($labels)->values()->map(fn (string $label, int $index) => [
        'id' => (string) ($index + 1),
        'type' => 'deployments',
        'attributes' => [
            'commit' => [
                'hash' => str_pad((string) $index, 40, '0'),
                'author' => 'Jane Doe',
                'message' => "Deployment letter {$label}",
                'branch' => 'main',
            ],
            'type' => 'Manual',
            'status' => 'finished',
            'started_at' => now()->subDays(count($labels) - $index)->toIso8601String(),
            'ended_at' => now()->subDays(count($labels) - $index)->addSeconds(30)->toIso8601String(),
            'created_at' => now()->subDays(count($labels) - $index)->toIso8601String(),
            'updated_at' => now()->subDays(count($labels) - $index)->addSeconds(30)->toIso8601String(),
        ],
    ])->values()->all();

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
        ListDeploymentsRequest::class => MockResponse::make([
            'data' => $deployments,
            'meta' => ['next_cursor' => null],
        ], 200),
    ]);

    livewire(ListDeployments::class)
        ->assertSuccessful()
        ->assertSeeText('Deployment letter O')
        ->assertDontSeeText('Deployment letter A')
        ->assertSeeText('15 results')
        ->call('gotoPage', 2)
        ->assertSeeText('Deployment letter A')
        ->assertDontSeeText('Deployment letter O');
});
