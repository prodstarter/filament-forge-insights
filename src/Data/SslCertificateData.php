<?php

namespace Prodstarter\FilamentForgeInsights\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Prodstarter\FilamentForgeInsights\Support\HealthStatus;

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

    /**
     * Forge doesn't expose a real expiry date, but Let's Encrypt certificates
     * are always issued for 90 days and Forge auto-renews them well before
     * that (its `updated_at` moves every time a renewal succeeds), so
     * `updated_at + 90 days` is a reliable estimate for an installed cert.
     */
    public function estimatedExpiresAt(): ?CarbonImmutable
    {
        if ($this->type !== 'letsencrypt' || ! $this->isInstalled() || ! $this->updatedAt) {
            return null;
        }

        return $this->updatedAt->addDays(90);
    }

    public function estimatedDaysRemaining(): ?int
    {
        $expiresAt = $this->estimatedExpiresAt();

        if (! $expiresAt) {
            return null;
        }

        return (int) floor(($expiresAt->getTimestamp() - now()->getTimestamp()) / 86400);
    }

    public function health(): HealthStatus
    {
        if ($this->requestStatus === 'failed') {
            return HealthStatus::Critical;
        }

        if (! $this->isInstalled() || ! $this->active) {
            return HealthStatus::Unknown;
        }

        $daysRemaining = $this->estimatedDaysRemaining();

        if (is_null($daysRemaining)) {
            return HealthStatus::Healthy;
        }

        if ($daysRemaining <= 0) {
            return HealthStatus::Critical;
        }

        if ($daysRemaining <= 14) {
            return HealthStatus::Attention;
        }

        return HealthStatus::Healthy;
    }
}
