<?php

namespace Prodstarter\FilamentForgeInsights\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

readonly class ScheduledJobData
{
    /**
     * @param  array<string, mixed>  $raw  The untouched API attributes, for anything not yet mapped to a named property.
     */
    public function __construct(
        public int | string $id,
        public int | string $serverId,
        public ?string $name,
        public ?string $command,
        public ?string $status,
        public ?string $user,
        public ?string $frequency,
        public ?string $cron,
        public ?CarbonImmutable $nextRunAt,
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
            command: Arr::get($data, 'command'),
            status: Arr::get($data, 'status'),
            user: Arr::get($data, 'user'),
            frequency: Arr::get($data, 'frequency'),
            cron: Arr::get($data, 'cron'),
            nextRunAt: filled(Arr::get($data, 'next_run_time'))
                ? CarbonImmutable::parse($data['next_run_time'])
                : null,
            createdAt: filled(Arr::get($data, 'created_at'))
                ? CarbonImmutable::parse($data['created_at'])
                : null,
            raw: $data,
        );
    }

    public function isActive(): bool
    {
        return $this->status === 'installed';
    }
}
