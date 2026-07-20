<?php

use Prodstarter\FilamentForgeInsights\FilamentForgeInsightsPlugin;

it('proxies fluent configuration into the config repository on register', function () {
    $panel = filament()->getPanel('admin');

    FilamentForgeInsightsPlugin::make()
        ->token('test-token')
        ->organization(12345)
        ->navigationGroup('Client Infrastructure')
        ->cacheFor(600)
        ->register($panel);

    expect(config('forge-insights.token'))->toBe('test-token');
    expect(config('forge-insights.organization'))->toBe(12345);
    expect(config('forge-insights.navigation_group'))->toBe('Client Infrastructure');
    expect(config('forge-insights.cache.servers'))->toBe(600);
    expect(config('forge-insights.cache.ssl'))->toBe(600);
});

it('defaults to read only', function () {
    expect(FilamentForgeInsightsPlugin::make()->isReadOnly())->toBeTrue();
});
