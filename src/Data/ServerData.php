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
        public ?string $ubuntuVersion,
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
            ubuntuVersion: Arr::get($data, 'ubuntu_version'),
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

    /**
     * Forge's PHP version slugs (e.g. "php84") formatted for display (e.g. "8.4").
     */
    public function formattedPhpVersion(): ?string
    {
        if (blank($this->phpVersion)) {
            return null;
        }

        if (! preg_match('/^php(\d)(\d+)$/i', $this->phpVersion, $matches)) {
            return $this->phpVersion;
        }

        return "{$matches[1]}.{$matches[2]}";
    }

    /**
     * Forge's database type slugs (e.g. "mysql8", "mariadb106") formatted for
     * display (e.g. "MySQL 8", "MariaDB 10.6").
     */
    public function formattedDatabaseType(): ?string
    {
        if (blank($this->databaseType)) {
            return null;
        }

        if (! preg_match('/^([a-z]+?)(\d+)$/i', $this->databaseType, $matches)) {
            return ucfirst($this->databaseType);
        }

        [, $engine, $version] = $matches;

        $engineNames = [
            'mysql' => 'MySQL',
            'mariadb' => 'MariaDB',
            'postgres' => 'PostgreSQL',
        ];

        $label = $engineNames[strtolower($engine)] ?? ucfirst($engine);
        $version = strlen($version) > 2 ? implode('.', str_split($version, 2)) : $version;

        return "{$label} {$version}";
    }

    /**
     * Forge's provider slugs (e.g. "ocean2") formatted as a recognizable
     * hosting provider name. Unrecognized slugs are title-cased as a
     * best-effort fallback rather than guessed at.
     */
    public function formattedProvider(): ?string
    {
        if (blank($this->provider)) {
            return null;
        }

        $providerNames = [
            'ocean' => 'DigitalOcean',
            'ocean2' => 'DigitalOcean',
            'aws' => 'AWS',
            'linode' => 'Linode',
            'vultr' => 'Vultr',
            'hetzner' => 'Hetzner',
            'custom' => 'Custom Server',
        ];

        return $providerNames[strtolower($this->provider)] ?? ucfirst($this->provider);
    }
}
