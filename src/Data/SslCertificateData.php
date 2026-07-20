<?php

namespace Prodstarter\FilamentForgeInsights\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

/**
 * Forge's certificates API does not expose an expiry date directly — only
 * issuance/request status and the last time the certificate record was
 * updated (which, for Let's Encrypt certs, tracks Forge's own renewal
 * checks).
 */
readonly class SslCertificateData
{
    /**
     * @param  array<string, mixed>  $raw  The untouched API attributes, for anything not yet mapped to a named property.
     */
    public function __construct(
        public int | string $id,
        public int | string $serverId,
        public int | string $siteId,
        public ?string $type,
        public ?string $status,
        public ?string $requestStatus,
        public bool $active,
        public ?CarbonImmutable $createdAt,
        public ?CarbonImmutable $updatedAt,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int | string $serverId, int | string $siteId, array $data): self
    {
        return new self(
            id: Arr::get($data, 'id'),
            serverId: $serverId,
            siteId: $siteId,
            type: Arr::get($data, 'type'),
            status: Arr::get($data, 'status'),
            requestStatus: Arr::get($data, 'request_status'),
            active: (bool) Arr::get($data, 'active', false),
            createdAt: filled(Arr::get($data, 'created_at'))
                ? CarbonImmutable::parse($data['created_at'])
                : null,
            updatedAt: filled(Arr::get($data, 'updated_at'))
                ? CarbonImmutable::parse($data['updated_at'])
                : null,
            raw: $data,
        );
    }

    public function isInstalled(): bool
    {
        return $this->status === 'installed';
    }
}
