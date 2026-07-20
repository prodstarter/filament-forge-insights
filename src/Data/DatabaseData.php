<?php

namespace Prodstarter\FilamentForgeInsights\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

readonly class DatabaseData
{
    /**
     * @param  array<string, mixed>  $raw  The untouched API attributes, for anything not yet mapped to a named property.
     */
    public function __construct(
        public int | string $id,
        public int | string $serverId,
        public ?string $name,
        public ?string $status,
        public ?CarbonImmutable $createdAt,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int | string $serverId, array $data): self
    {
        return new self(
            id: Arr::get($data, 'id'),
            serverId: $serverId,
            name: Arr::get($data, 'name'),
            status: Arr::get($data, 'status'),
            createdAt: filled(Arr::get($data, 'created_at'))
                ? CarbonImmutable::parse($data['created_at'])
                : null,
            raw: $data,
        );
    }
}
