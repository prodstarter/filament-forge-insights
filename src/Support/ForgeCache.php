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
     * The epoch only changes when flush() is explicitly called (the
     * Settings page's "Sync Now"), so re-reading it from the cache backend
     * on every single get()/put()/has() call — which key() does — is pure
     * waste within one request: a dashboard render touching a few dozen
     * cache keys was paying for a few dozen extra queries just for this.
     * Memoized per PHP process, which for a standard request-per-process
     * setup (PHP-FPM, Herd) means per request; a long-running worker
     * (queue, Octane) could see a flush() from elsewhere lag by up to that
     * worker's lifetime, which is an acceptable trade for this plugin's
     * read-mostly, TTL-bounded data.
     */
    protected static ?int $memoizedEpoch = null;

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
        $value = static::get($key);

        if (! is_null($value)) {
            return $value;
        }

        $value = $callback();

        static::put($key, $value, $ttl);

        return $value;
    }

    /**
     * Read a key directly, decoding it the same way remember() does,
     * without computing or storing anything on a miss. Returns null for a
     * miss (or a corrupted entry) — every value this class ever stores is a
     * Collection, never null itself, so null is an unambiguous "not
     * cached" signal here.
     *
     * Prefer this over has() + remember() when you already know what to do
     * on a miss yourself (e.g. batching several misses into one pooled
     * fetch): has() then remember() costs two cache reads for the same key
     * where this costs one.
     */
    public static function get(string $key): mixed
    {
        $cached = Cache::get(static::key($key));

        if (! is_string($cached)) {
            return null;
        }

        $value = @unserialize(base64_decode($cached));

        return $value === false ? null : $value;
    }

    /**
     * Store a value directly, for when the caller already knows it needs
     * fetching (e.g. one item from a pooled batch) and doesn't need
     * remember()'s own read-first check repeated.
     */
    public static function put(string $key, mixed $value, int $ttl): void
    {
        Cache::put(static::key($key), base64_encode(serialize($value)), $ttl);
    }

    /**
     * Check whether a key is already cached, without computing or storing
     * anything on a miss.
     */
    public static function has(string $key): bool
    {
        return Cache::has(static::key($key));
    }

    public static function key(string $key): string
    {
        return 'forge-insights:' . static::epoch() . ':' . $key;
    }

    public static function flush(): void
    {
        static::$memoizedEpoch = static::epoch() + 1;

        Cache::put(static::EPOCH_KEY, static::$memoizedEpoch);
    }

    protected static function epoch(): int
    {
        return static::$memoizedEpoch ??= (int) Cache::get(static::EPOCH_KEY, 0);
    }
}
