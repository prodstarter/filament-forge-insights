<?php

namespace Prodstarter\FilamentForgeInsights\Data;

use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;

readonly class SiteData
{
    /**
     * @param  array<string, mixed>  $raw  The untouched API attributes, for anything not yet mapped to a named property.
     */
    public function __construct(
        public int | string $id,
        public int | string $serverId,
        public string $domain,
        public ?string $status,
        public ?string $url,
        public bool $isSecured,
        public ?string $webDirectory,
        public ?string $phpVersion,
        public ?string $deploymentStatus,
        public bool $quickDeploy,
        public bool $isolated,
        public ?string $repositoryProvider,
        public ?string $repositoryUrl,
        public ?string $repositoryBranch,
        public ?string $repositoryStatus,
        public ?string $appType,
        public ?CarbonImmutable $createdAt,
        public array $raw,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int | string $serverId, array $data): self
    {
        $repository = Arr::get($data, 'repository', []) ?? [];

        return new self(
            id: Arr::get($data, 'id'),
            serverId: $serverId,
            domain: (string) Arr::get($data, 'name'),
            status: Arr::get($data, 'status'),
            url: Arr::get($data, 'url'),
            isSecured: (bool) Arr::get($data, 'https', false),
            webDirectory: Arr::get($data, 'web_directory'),
            phpVersion: Arr::get($data, 'php_version'),
            deploymentStatus: Arr::get($data, 'deployment_status'),
            quickDeploy: (bool) Arr::get($data, 'quick_deploy', false),
            isolated: (bool) Arr::get($data, 'isolated', false),
            repositoryProvider: Arr::get($repository, 'provider'),
            repositoryUrl: Arr::get($repository, 'url'),
            repositoryBranch: Arr::get($repository, 'branch'),
            repositoryStatus: Arr::get($repository, 'status'),
            appType: Arr::get($data, 'app_type'),
            createdAt: filled(Arr::get($data, 'created_at'))
                ? CarbonImmutable::parse($data['created_at'])
                : null,
            raw: $data,
        );
    }
}
