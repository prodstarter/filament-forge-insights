<?php

namespace Prodstarter\FilamentForgeInsights\Support;

use Illuminate\Support\Collection;

/**
 * The Forge API returns JSON:API-shaped payloads: {"data": [{"id": ..., "type": ..., "attributes": {...}}]}.
 * This flattens each item into a single array (attributes + id) so DTOs can stay simple.
 */
class JsonApiResource
{
    /**
     * @param  array<string, mixed>  $response
     * @return Collection<int, array<string, mixed>>
     */
    public static function collection(array $response): Collection
    {
        return collect($response['data'] ?? [])->map(fn (array $item) => static::flattenItem($item));
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    public static function item(array $response): array
    {
        return static::flattenItem($response['data'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public static function flattenItem(array $item): array
    {
        $attributes = $item['attributes'] ?? [];

        return $attributes + ['id' => $item['id'] ?? null];
    }
}
