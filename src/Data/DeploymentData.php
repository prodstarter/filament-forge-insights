<?php

namespace Prodstarter\FilamentForgeInsights\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Prodstarter\FilamentForgeInsights\Support\HealthStatus;

readonly class DeploymentData
{
    /**
     * @param  array<string, mixed>  $raw  The untouched API attributes, for anything not yet mapped to a named property.
     */
    public function __construct(
        public int | string $id,
        public int | string $serverId,
        public int | string $siteId,
        public ?string $commitHash,
        public ?string $commitAuthor,
        public ?string $commitMessage,
        public ?string $commitBranch,
        public ?string $triggeredBy,
        public ?string $status,
        public ?CarbonImmutable $startedAt,
        public ?CarbonImmutable $endedAt,
        public ?CarbonImmutable $createdAt,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int | string $serverId, int | string $siteId, array $data): self
    {
        $commit = Arr::get($data, 'commit', []) ?? [];

        return new self(
            id: Arr::get($data, 'id'),
            serverId: $serverId,
            siteId: $siteId,
            commitHash: Arr::get($commit, 'hash'),
            commitAuthor: Arr::get($commit, 'author'),
            commitMessage: Arr::get($commit, 'message'),
            commitBranch: Arr::get($commit, 'branch'),
            triggeredBy: Arr::get($data, 'type'),
            status: Arr::get($data, 'status'),
            startedAt: filled(Arr::get($data, 'started_at'))
                ? CarbonImmutable::parse($data['started_at'])
                : null,
            endedAt: filled(Arr::get($data, 'ended_at'))
                ? CarbonImmutable::parse($data['ended_at'])
                : null,
            createdAt: filled(Arr::get($data, 'created_at'))
                ? CarbonImmutable::parse($data['created_at'])
                : null,
            raw: $data,
        );
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    /**
     * A Filament color name for the deployment's status badge.
     */
    public function statusColor(): string
    {
        return match ($this->status) {
            'finished' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    public function health(): HealthStatus
    {
        return match ($this->status) {
            'finished' => HealthStatus::Healthy,
            'failed' => HealthStatus::Critical,
            default => HealthStatus::Unknown,
        };
    }

    /**
     * A client-facing label for the deployment's status, using the plugin's
     * "Successful" / "Failed" health language rather than Forge's internal
     * "finished" / "failed" status values.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'finished' => 'Successful',
            'failed' => 'Failed',
            null => 'Unknown',
            default => ucfirst($this->status),
        };
    }

    public function durationInSeconds(): ?int
    {
        if (! $this->startedAt || ! $this->endedAt) {
            return null;
        }

        return (int) abs($this->endedAt->diffInSeconds($this->startedAt));
    }

    public function formattedDuration(): string
    {
        $seconds = $this->durationInSeconds();

        if (is_null($seconds)) {
            return '—';
        }

        if ($seconds < 60) {
            return "{$seconds}s";
        }

        return sprintf('%dm %ds', intdiv($seconds, 60), $seconds % 60);
    }
}
