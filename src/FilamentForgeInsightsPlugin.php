<?php

namespace Prodstarter\FilamentForgeInsights;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Prodstarter\FilamentForgeInsights\Filament\Pages\ListDatabases;
use Prodstarter\FilamentForgeInsights\Filament\Pages\ListDeployments;
use Prodstarter\FilamentForgeInsights\Filament\Pages\ListServers;
use Prodstarter\FilamentForgeInsights\Filament\Pages\ListSites;
use Prodstarter\FilamentForgeInsights\Filament\Pages\QueueWorkers;
use Prodstarter\FilamentForgeInsights\Filament\Pages\ScheduledJobs;
use Prodstarter\FilamentForgeInsights\Filament\Pages\ServerDashboard;
use Prodstarter\FilamentForgeInsights\Filament\Pages\Settings;
use Prodstarter\FilamentForgeInsights\Filament\Pages\SslOverview;
use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;

class FilamentForgeInsightsPlugin implements Plugin
{
    protected ?string $token = null;

    protected int | string | null $organization = null;

    protected ?string $navigationGroup = null;

    protected ?int $cacheSeconds = null;

    protected bool $readOnly = true;

    public function getId(): string
    {
        return 'filament-forge-insights';
    }

    public function register(Panel $panel): void
    {
        if (filled($this->token)) {
            config(['forge-insights.token' => $this->token]);
        }

        if (filled($this->organization)) {
            config(['forge-insights.organization' => $this->organization]);
        }

        if (filled($this->navigationGroup)) {
            config(['forge-insights.navigation_group' => $this->navigationGroup]);
        }

        if (! is_null($this->cacheSeconds)) {
            config(['forge-insights.cache' => array_fill_keys(
                array_keys(config('forge-insights.cache', [])),
                $this->cacheSeconds
            )]);
        }

        $panel->pages([
            ServerDashboard::class,
            ListServers::class,
            ListSites::class,
            ListDeployments::class,
            SslOverview::class,
            ListDatabases::class,
            ScheduledJobs::class,
            QueueWorkers::class,
            Settings::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        app(SettingsManager::class)->applyToConfig();
    }

    public function token(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function organization(int | string | null $organization): static
    {
        $this->organization = $organization;

        return $this;
    }

    public function navigationGroup(?string $navigationGroup): static
    {
        $this->navigationGroup = $navigationGroup;

        return $this;
    }

    /**
     * Override every resource's cache TTL with a single duration.
     */
    public function cacheFor(\DateInterval | \DateTimeInterface | int $duration): static
    {
        $this->cacheSeconds = $this->resolveSeconds($duration);

        return $this;
    }

    public function readOnly(bool $readOnly = true): static
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    public function isReadOnly(): bool
    {
        return $this->readOnly;
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    protected function resolveSeconds(\DateInterval | \DateTimeInterface | int $duration): int
    {
        if (is_int($duration)) {
            return $duration;
        }

        $now = now();

        $target = $duration instanceof \DateInterval
            ? $now->copy()->add($duration)
            : $duration;

        return max(0, $now->diffInSeconds($target, absolute: false));
    }
}
