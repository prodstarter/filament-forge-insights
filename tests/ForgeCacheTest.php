<?php

use Illuminate\Support\Facades\Cache;
use Prodstarter\FilamentForgeInsights\Support\ForgeCache;

it('caches the callback result and returns it on a second call without re-invoking the callback', function () {
    $calls = 0;

    $first = ForgeCache::remember('widget', 60, function () use (&$calls) {
        $calls++;

        return collect(['a', 'b']);
    });

    $second = ForgeCache::remember('widget', 60, function () use (&$calls) {
        $calls++;

        return collect(['a', 'b']);
    });

    expect($calls)->toBe(1);
    expect($first->all())->toBe($second->all());
});

it('round-trips a value containing bytes that are not valid UTF-8', function () {
    $binary = "corrupt-prone-bytes:\xC0\xC1\xF5\xFF";

    $result = ForgeCache::remember('binary-safe', 60, fn () => collect([$binary]));

    expect($result->first())->toBe($binary);
});

it('treats a corrupted cache entry as a miss and recomputes instead of throwing', function () {
    Cache::put(ForgeCache::key('broken'), 'not-valid-base64-or-serialized-data', 60);

    $calls = 0;

    $result = ForgeCache::remember('broken', 60, function () use (&$calls) {
        $calls++;

        return collect(['recovered']);
    });

    expect($calls)->toBe(1);
    expect($result->first())->toBe('recovered');
});

it('busts the cache when flush is called', function () {
    $calls = 0;

    ForgeCache::remember('flush-test', 60, function () use (&$calls) {
        $calls++;

        return 'value';
    });

    ForgeCache::flush();

    ForgeCache::remember('flush-test', 60, function () use (&$calls) {
        $calls++;

        return 'value';
    });

    expect($calls)->toBe(2);
});
