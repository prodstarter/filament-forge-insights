<?php

namespace Prodstarter\FilamentForgeInsights\Settings;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Prodstarter\FilamentForgeInsights\Forge\ForgeAccountConnector;
use Prodstarter\FilamentForgeInsights\Forge\ForgeConnector;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListOrganizationsRequest;
use Prodstarter\FilamentForgeInsights\Forge\Requests\ListServersRequest;
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
    }

    public function save(string $token, string $organization): ForgeInsightsSetting
    {
        $setting = ForgeInsightsSetting::query()->firstOrNew();
        $setting->fill([
            'token' => $token,
            'organization' => $organization,
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
