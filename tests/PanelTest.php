<?php

use Prodstarter\FilamentForgeInsights\FilamentForgeInsightsPlugin;
use Workbench\App\Models\User;
use Workbench\App\Providers\Filament\TestPanelProvider;

it('registers the panel provider', function () {
    expect(app()->getProviders(TestPanelProvider::class))->not->toBeEmpty();
    expect(array_keys(filament()->getPanels()))->toContain('admin');
});

it('boots the workbench panel with the plugin registered', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertSuccessful();

    expect(filament()->getPanel('admin')->getPlugin('filament-forge-insights'))
        ->toBeInstanceOf(FilamentForgeInsightsPlugin::class);
});
