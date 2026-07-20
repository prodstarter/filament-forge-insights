<?php

use Prodstarter\FilamentForgeInsights\Filament\Pages\Settings;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListOrganizationsRequest;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListServersRequest;
use Prodstarter\FilamentForgeInsights\Settings\ForgeInsightsSetting;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Workbench\App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    MockClient::destroyGlobal();
});

it('can render', function () {
    livewire(Settings::class)->assertSuccessful();
});

it('can connect and persist settings after a successful test', function () {
    MockClient::global([
        ListOrganizationsRequest::class => MockResponse::make([
            'data' => [
                [
                    'id' => 'org-1',
                    'type' => 'organizations',
                    'attributes' => ['name' => 'Acme', 'slug' => 'acme'],
                ],
            ],
        ], 200),
        ListServersRequest::class => MockResponse::make(['data' => [], 'meta' => ['next_cursor' => null]], 200),
    ]);

    livewire(Settings::class)
        ->fillForm(['token' => 'test-token', 'organization' => 'acme'])
        ->call('save')
        ->assertNotified();

    expect(ForgeInsightsSetting::query()->first())
        ->organization->toBe('acme');
});

it('notifies but does not persist when the connection test fails', function () {
    MockClient::global([
        ListOrganizationsRequest::class => MockResponse::make([
            'data' => [
                [
                    'id' => 'org-1',
                    'type' => 'organizations',
                    'attributes' => ['name' => 'Acme', 'slug' => 'acme'],
                ],
            ],
        ], 200),
        ListServersRequest::class => MockResponse::make(['message' => 'Unauthorized'], 401),
    ]);

    livewire(Settings::class)
        ->fillForm(['token' => 'bad-token', 'organization' => 'acme'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(ForgeInsightsSetting::query()->first())->toBeNull();
});
