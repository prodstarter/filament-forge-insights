<?php

namespace Prodstarter\FilamentForgeInsights\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

readonly class ServerData
{
    /**
     * @param  array<string, mixed>  $raw  The untouched API attributes, for anything not yet mapped to a named property.
     */
    public function __construct(
        public int | string $id,
        public string $name,
        public ?string $type,
        public ?string $ipAddress,
        public ?string $privateIpAddress,
        public ?string $provider,
        public ?string $region,
        public ?string $size,
        public ?string $phpVersion,
        public ?string $databaseType,
        public bool $isReady,
        public ?string $connectionStatus,
        public ?CarbonImmutable $createdAt,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::get($data, 'id'),
            name: (string) Arr::get($data, 'name'),
            type: Arr::get($data, 'type'),
            ipAddress: Arr::get($data, 'ip_address'),
            privateIpAddress: Arr::get($data, 'private_ip_address'),
            provider: Arr::get($data, 'provider'),
            region: Arr::get($data, 'region'),
            size: Arr::get($data, 'size'),
            phpVersion: Arr::get($data, 'php_version'),
            databaseType: Arr::get($data, 'database_type'),
            isReady: (bool) Arr::get($data, 'is_ready', false),
            connectionStatus: Arr::get($data, 'connection_status'),
            createdAt: filled(Arr::get($data, 'created_at'))
                ? CarbonImmutable::parse($data['created_at'])
                : null,
            raw: $data,
        );
    }

    public function isOnline(): bool
    {
        return $this->connectionStatus === 'successful';
    }
}
