<?php

use Prodstarter\FilamentForgeInsights\Filament\Pages\QueueWorkers;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListServersRequest;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListWorkersRequest;
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
    livewire(QueueWorkers::class)->assertSuccessful();
});

it('renders workers across every server when connected', function () {
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
        ListWorkersRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/workers.json'), true),
            200,
        ),
    ]);

    livewire(QueueWorkers::class)
        ->assertSuccessful()
        ->assertSeeText('php artisan queue:work')
        ->assertSeeText('app-production');
});
