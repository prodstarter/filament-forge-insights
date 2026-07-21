<?php

namespace Prodstarter\FilamentForgeInsights\Settings;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Prodstarter\FilamentForgeInsights\Forge\ForgeAccountConnector;
use Prodstarter\FilamentForgeInsights\Forge\ForgeConnector;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListOrganizationsRequest;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListServersRequest;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListSitesRequest;
use Prodstarter\FilamentForgeInsights\Support\JsonApiResource;

class SettingsManager
{
    public function current(): ?ForgeInsightsSetting
    {
        if (! $this->isInstalled()) {
            return null;
        }

        return ForgeInsightsSetting::query()->first();
    }

    public function isConnected(): bool
    {
        $setting = $this->current();

        return filled($setting?->token) && filled($setting->organization);
    }

    public function isScopedToServer(): bool
    {
        return filled($this->current()?->server);
    }

    public function isScopedToSite(): bool
    {
        return filled($this->current()?->site);
    }

    public function scopedServerId(): ?string
    {
        return $this->current()?->server;
    }

    public function scopedSiteId(): ?string
    {
        return $this->current()?->site;
    }

    /**
     * Persist DB-configured settings on top of the config/env defaults, so the
     * rest of the plugin (repositories, connectors) can keep reading plain
     * config() values without knowing where they came from.
     */
    public function applyToConfig(): void
    {
        $setting = $this->current();

        if (! $setting) {
            return;
        }

        if (filled($setting->token)) {
            config(['forge-insights.token' => $setting->token]);
        }

        if (filled($setting->organization)) {
            config(['forge-insights.organization' => $setting->organization]);
        }

        config([
            'forge-insights.server' => $setting->server,
            'forge-insights.site' => $setting->site,
        ]);
    }

    public function save(string $token, string $organization, ?string $server = null, ?string $site = null): ForgeInsightsSetting
    {
        $setting = ForgeInsightsSetting::query()->firstOrNew();
        $setting->fill([
            'token' => $token,
            'organization' => $organization,
            'server' => $server,
            'site' => $site,
            'connected_at' => now(),
        ]);
        $setting->save();

        return $setting;
    }

    /**
     * @return Collection<int, array{slug: string, name: string}>
     */
    public function listOrganizations(string $token): Collection
    {
        $response = (new ForgeAccountConnector($token))->send(new ListOrganizationsRequest);

        return JsonApiResource::collection($response->json())
            ->map(fn (array $organization) => [
                'slug' => (string) $organization['slug'],
                'name' => (string) $organization['name'],
            ]);
    }

    /**
     * @return Collection<int, array{id: string, name: string}>
     */
    public function listServers(string $token, string $organization): Collection
    {
        return collect(
            (new ForgeConnector($token, $organization))
                ->paginate(new ListServersRequest)
                ->collect()
                ->map(fn (array $item) => JsonApiResource::flattenItem($item))
                ->map(fn (array $server) => [
                    'id' => (string) $server['id'],
                    'name' => (string) $server['name'],
                ])
                ->all(),
        );
    }

    /**
     * @return Collection<int, array{id: string, name: string}>
     */
    public function listSites(string $token, string $organization, string $serverId): Collection
    {
        return collect(
            (new ForgeConnector($token, $organization))
                ->paginate(new ListSitesRequest($serverId))
                ->collect()
                ->map(fn (array $item) => JsonApiResource::flattenItem($item))
                ->map(fn (array $site) => [
                    'id' => (string) $site['id'],
                    'name' => (string) $site['name'],
                ])
                ->all(),
        );
    }

    public function testConnection(string $token, string $organization): bool
    {
        $response = (new ForgeConnector($token, $organization))->send(new ListServersRequest);

        return $response->successful();
    }

    protected function isInstalled(): bool
    {
        return Schema::hasTable('forge_insights_settings');
    }
}
