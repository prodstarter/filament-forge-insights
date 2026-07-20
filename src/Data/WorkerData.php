<?php

namespace Prodstarter\FilamentForgeInsights\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

/**
 * Represents a Forge "background process" (daemon) — the server-level
 * process manager Forge exposes via the API. This is the closest real
 * analogue to the PRD's "queue worker" concept; Forge's API does not expose
 * per-worker queue connection/queue-name metadata separately from this.
 */
readonly class WorkerData
{
    /**
     * @param  array<string, mixed>  $raw  The untouched API attributes, for anything not yet mapped to a named property.
     */
    public function __construct(
        public int | string $id,
        public int | string $serverId,
        public ?string $command,
        public ?string $user,
        public ?string $directory,
        public int $processes,
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
            command: Arr::get($data, 'command'),
            user: Arr::get($data, 'user'),
            directory: Arr::get($data, 'directory'),
            processes: (int) Arr::get($data, 'processes', 1),
            status: Arr::get($data, 'status'),
            createdAt: filled(Arr::get($data, 'created_at'))
                ? CarbonImmutable::parse($data['created_at'])
                : null,
            raw: $data,
        );
    }

    public function isRunning(): bool
    {
        return $this->status === 'installed';
    }
}
