<?php

use Prodstarter\FilamentForgeInsights\Filament\Pages\ScheduledJobs;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListScheduledJobsRequest;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListServersRequest;
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
    livewire(ScheduledJobs::class)->assertSuccessful();
});

it('renders scheduled jobs across every server when connected', function () {
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
        ListScheduledJobsRequest::class => MockResponse::make(
            json_decode(file_get_contents(__DIR__ . '/Fixtures/scheduled-jobs.json'), true),
            200,
        ),
    ]);

    livewire(ScheduledJobs::class)
        ->assertSuccessful()
        ->assertSeeText('Update Composer')
        ->assertSeeText('app-production');
});
