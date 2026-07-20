<?php

namespace Prodstarter\FilamentForgeInsights\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache-key-epoch based invalidation, so "Sync Now" works identically on
 * every cache driver (including ones without tag support, like database
 * or file) instead of relying on Cache::tags().
 */
class ForgeCache
{
    protected const EPOCH_KEY = 'forge-insights:cache-epoch';

    /**
     * Not all cache drivers are binary-safe for arbitrary serialized PHP
     * objects (Laravel's database driver, for example, only base64-encodes
     * values for Postgres/SQLite — MySQL connections store the raw
     * serialize() output as-is, which a non-UTF8-safe byte sequence
     * anywhere in the cached payload, e.g. inside raw API text, can corrupt
     * silently). Base64-encoding here ourselves keeps what we hand to
     * Cache::put() plain ASCII, regardless of driver or database.
     *
     * @param  Closure(): mixed  $callback
     */
    public static function remember(string $key, int $ttl, Closure $callback): mixed
    {
        $cacheKey = static::key($key);

        $cached = Cache::get($cacheKey);

        if (is_string($cached)) {
            $value = @unserialize(base64_decode($cached));

            if ($value !== false) {
                return $value;
            }
        }

        $value = $callback();

        Cache::put($cacheKey, base64_encode(serialize($value)), $ttl);

        return $value;
    }

    public static function key(string $key): string
    {
        return 'forge-insights:' . static::epoch() . ':' . $key;
    }

    public static function flush(): void
    {
        Cache::put(static::EPOCH_KEY, static::epoch() + 1);
    }

    protected static function epoch(): int
    {
        return (int) Cache::get(static::EPOCH_KEY, 0);
    }
}
