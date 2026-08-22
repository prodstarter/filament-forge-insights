<?php

namespace Prodstarter\FilamentForgeInsights\Filament\Pages;

use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Prodstarter\FilamentForgeInsights\Data\DeploymentData;
use Prodstarter\FilamentForgeInsights\Data\ServerData;
use Prodstarter\FilamentForgeInsights\Data\SiteData;
use Prodstarter\FilamentForgeInsights\Data\SslCertificateData;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\DeploymentRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SiteRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SslRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;
use Prodstarter\FilamentForgeInsights\Support\ForgeCache;
use Prodstarter\FilamentForgeInsights\Support\HealthStatus;
use UnitEnum;

class ServerDashboard extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Overview';

    protected static ?int $navigationSort = -1;

    protected string $view = 'filament-forge-insights::pages.server-dashboard';

    public bool $ipRevealed = false;

    /**
     * The dashboard calls servers()/sitesForServer()/recentDeployments()
     * from half a dozen independent methods while building the page (one
     * per card, section, and attention check). Every one of those repository
     * calls is cached across *requests* via ForgeCache, but a cache hit
     * still costs a real round trip to the cache backend — with no
     * memoization here, a single render on an org with a handful of servers
     * was issuing 800+ of those round trips. These properties make each
     * underlying fetch happen at most once per render.
     */
    protected ?Collection $memoizedServers = null;

    /**
     * @var array<int|string, Collection<int, SiteData>>
     */
    protected array $memoizedSitesByServer = [];

    protected ?Collection $memoizedDeployments = null;

    protected bool $sslCertificateResolved = false;

    protected ?SslCertificateData $memoizedSslCertificate = null;

    /**
     * The largest deployment history any scope's view actually needs
     * (the 30-day activity window). Smaller requests (e.g. "just the last
     * deployment") slice this single fetch instead of asking the repository
     * again — the repository's own cost is dominated by how many
     * servers/sites it walks, not by the limit, so re-fetching at a smaller
     * limit does the same work for no benefit.
     */
    protected const MAX_RECENT_DEPLOYMENTS = 100;

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return config('forge-insights.navigation_group', 'Infrastructure');
    }

    public function toggleIpVisibility(): void
    {
        $this->ipRevealed = ! $this->ipRevealed;
    }

    /**
     * The page's own identity header (the site/server/organization name and
     * health badge) replaces Filament's default page heading entirely.
     */
    public function getHeading(): string | Htmlable | null
    {
        return null;
    }

    public function getSubheading(): string | Htmlable | null
    {
        return null;
    }

    /**
     * 'disconnected' | 'site' | 'server' | 'organization'
     */
    public function getScope(): string
    {
        $manager = app(SettingsManager::class);

        if (! $manager->isConnected()) {
            return 'disconnected';
        }

        if ($manager->isScopedToSite()) {
            return 'site';
        }

        if ($manager->isScopedToServer()) {
            return 'server';
        }

        return 'organization';
    }

    /**
     * A dashboard-wide "last checked" timestamp. Real freshness is governed
     * by each resource's own cache TTL, so this piggybacks on the site
     * cache's TTL as a representative approximation of when the underlying
     * Forge data was actually pulled, rather than claiming a precision we
     * don't have.
     */
    public function getLastCheckedAt(): CarbonImmutable
    {
        return ForgeCache::remember(
            'dashboard:last-checked:' . config('forge-insights.organization'),
            config('forge-insights.cache.sites', 600),
            fn () => now()->toImmutable(),
        );
    }

    /**
     * @return array{title: string, subtitle: ?string, health: HealthStatus, healthLabel: string, websiteUrl: ?string}
     */
    public function getIdentityHeader(): array
    {
        $scope = $this->getScope();

        if ($scope === 'site') {
            [$server, $site] = $this->siteContext();

            return [
                'title' => $site?->domain ?? 'Server Dashboard',
                'subtitle' => $server
                    ? implode(' · ', array_filter([$server->formattedProvider(), $server->region]))
                    : null,
                'health' => $this->siteHealth($server, $site),
                'websiteUrl' => $site?->url,
            ];
        }

        if ($scope === 'server') {
            $server = $this->servers()->first();

            return [
                'title' => $server?->name ?? 'Server Dashboard',
                'subtitle' => $server
                    ? implode(' · ', array_filter([$server->formattedProvider(), $server->region]))
                    : null,
                'health' => $server && $server->isOnline() ? HealthStatus::Healthy : HealthStatus::Critical,
                'websiteUrl' => null,
            ];
        }

        if ($scope === 'organization') {
            $serverCount = $this->servers()->count();

            return [
                'title' => 'Infrastructure Overview',
                'subtitle' => $serverCount . ' server' . ($serverCount === 1 ? '' : 's') . ' connected',
                'health' => HealthStatus::Healthy,
                'websiteUrl' => null,
            ];
        }

        return [
            'title' => 'Server Dashboard',
            'subtitle' => 'Not connected to Forge',
            'health' => HealthStatus::Unknown,
            'websiteUrl' => null,
        ];
    }

    /**
     * @return array<int, array{label: string, icon: BackedEnum, health: HealthStatus, status: string, lines: array<int, string>}>
     */
    public function getCards(): array
    {
        $scope = $this->getScope();

        if ($scope === 'site') {
            [$server, $site] = $this->siteContext();
            $ssl = $this->sslCertificate();
            $lastDeployment = $this->recentDeployments(1)->first();

            $isOnline = $site?->status === 'installed';

            return [
                [
                    'label' => 'Website',
                    'icon' => Heroicon::OutlinedGlobeAlt,
                    'health' => $isOnline ? HealthStatus::Healthy : HealthStatus::Critical,
                    'status' => $isOnline ? 'Online' : ($site?->status ? ucfirst($site->status) : 'Unknown'),
                    'lines' => array_filter([$site?->isSecured ? 'HTTPS' : 'No HTTPS', $site?->domain]),
                ],
                [
                    'label' => 'Server',
                    'icon' => Heroicon::OutlinedServer,
                    'health' => $server?->isOnline() ? HealthStatus::Healthy : HealthStatus::Critical,
                    'status' => $server?->isOnline() ? 'Healthy' : 'Offline',
                    'lines' => array_filter([$server?->name, $server?->region]),
                ],
                [
                    'label' => 'SSL',
                    'icon' => Heroicon::OutlinedLockClosed,
                    'health' => $ssl?->health() ?? HealthStatus::Unknown,
                    'status' => $this->sslStatusLabel($ssl),
                    'lines' => $this->sslCardLines($ssl),
                ],
                [
                    'label' => 'Deployment',
                    'icon' => Heroicon::OutlinedRocketLaunch,
                    'health' => $lastDeployment?->health() ?? HealthStatus::Unknown,
                    'status' => $lastDeployment ? $lastDeployment->statusLabel() : 'No deployments',
                    'lines' => array_filter([
                        $lastDeployment ? "{$lastDeployment->commitBranch} · {$lastDeployment->formattedDuration()}" : null,
                        $lastDeployment?->createdAt?->diffForHumans(),
                    ]),
                ],
            ];
        }

        if ($scope === 'server') {
            $server = $this->servers()->first();
            $siteCount = $server ? $this->sitesForServer($server->id)->count() : 0;
            $deployments = $this->recentDeployments(50);
            $deploymentsToday = $deployments->filter(fn (DeploymentData $deployment) => $deployment->createdAt?->isToday())->count();
            $lastDeployment = $deployments->first();

            return [
                [
                    'label' => 'Server',
                    'icon' => Heroicon::OutlinedServer,
                    'health' => $server?->isOnline() ? HealthStatus::Healthy : HealthStatus::Critical,
                    'status' => $server?->isOnline() ? 'Online' : 'Offline',
                    'lines' => array_filter([$server?->name, $server?->region]),
                ],
                [
                    'label' => 'Sites',
                    'icon' => Heroicon::OutlinedGlobeAlt,
                    'health' => HealthStatus::Healthy,
                    'status' => (string) $siteCount,
                    'lines' => ['Hosted on this server'],
                ],
                [
                    'label' => 'PHP',
                    'icon' => Heroicon::OutlinedCodeBracket,
                    'health' => HealthStatus::Healthy,
                    'status' => $server?->formattedPhpVersion() ?? '—',
                    'lines' => array_filter([$server?->formattedDatabaseType()]),
                ],
                [
                    'label' => 'Deployment',
                    'icon' => Heroicon::OutlinedRocketLaunch,
                    'health' => $lastDeployment?->health() ?? HealthStatus::Unknown,
                    'status' => (string) $deploymentsToday . ' today',
                    'lines' => array_filter([$lastDeployment?->createdAt?->diffForHumans()]),
                ],
            ];
        }

        if ($scope === 'organization') {
            $servers = $this->servers();
            $siteCount = $servers->sum(fn (ServerData $server) => $this->sitesForServer($server->id)->count());
            $deployments = $this->recentDeployments(50);
            $deploymentsToday = $deployments->filter(fn (DeploymentData $deployment) => $deployment->createdAt?->isToday())->count();
            $offlineServers = $servers->filter(fn (ServerData $server) => ! $server->isOnline())->count();

            return [
                [
                    'label' => 'Servers',
                    'icon' => Heroicon::OutlinedServerStack,
                    'health' => $offlineServers > 0 ? HealthStatus::Attention : HealthStatus::Healthy,
                    'status' => (string) $servers->count(),
                    'lines' => [$offlineServers > 0 ? "{$offlineServers} offline" : 'All online'],
                ],
                [
                    'label' => 'Websites',
                    'icon' => Heroicon::OutlinedGlobeAlt,
                    'health' => HealthStatus::Healthy,
                    'status' => (string) $siteCount,
                    'lines' => ['Across all servers'],
                ],
                [
                    'label' => 'Deployments',
                    'icon' => Heroicon::OutlinedRocketLaunch,
                    'health' => HealthStatus::Healthy,
                    'status' => (string) $deploymentsToday,
                    'lines' => ['Today'],
                ],
                [
                    'label' => 'Forge Connection',
                    'icon' => Heroicon::OutlinedShieldCheck,
                    'health' => HealthStatus::Healthy,
                    'status' => 'Connected',
                    'lines' => [(string) config('forge-insights.organization')],
                ],
            ];
        }

        return [];
    }

    /**
     * @return array{server: ?ServerData, site: ?SiteData, ssl: ?SslCertificateData, ipRevealed: bool}
     */
    public function getSiteOverview(): array
    {
        [$server, $site] = $this->siteContext();

        return [
            'server' => $server,
            'site' => $site,
            'ssl' => $this->sslCertificate(),
            'ipRevealed' => $this->ipRevealed,
        ];
    }

    /**
     * @return array{server: ?ServerData, ipRevealed: bool}
     */
    public function getServerOverview(): array
    {
        return [
            'server' => $this->servers()->first(),
            'ipRevealed' => $this->ipRevealed,
        ];
    }

    /**
     * @return array{servers: Collection<int, array{server: ServerData, siteCount: int}>, totalCount: int, viewAllUrl: string}
     */
    public function getOrganizationOverview(): array
    {
        $servers = $this->servers();

        $rows = $servers
            ->sortByDesc(fn (ServerData $server) => $server->createdAt)
            ->take(5)
            ->map(fn (ServerData $server) => [
                'server' => $server,
                'siteCount' => $this->sitesForServer($server->id)->count(),
            ])
            ->values();

        return [
            'servers' => $rows,
            'totalCount' => $servers->count(),
            'viewAllUrl' => ListServers::getUrl(),
        ];
    }

    /**
     * @return array{summary: array{successful: int, failed: int, successRate: int, periodDays: int}, recent: Collection<int, array<string, mixed>>, showSiteColumn: bool, viewAllUrl: string}
     */
    public function getDeploymentActivity(): array
    {
        $scope = $this->getScope();
        $windowStart = now()->subDays(30);

        $deployments = $this->recentDeployments(100)
            ->filter(fn (DeploymentData $deployment) => $deployment->createdAt?->greaterThanOrEqualTo($windowStart));

        $successful = $deployments->filter(fn (DeploymentData $deployment) => $deployment->isFinished())->count();
        $failed = $deployments->filter(fn (DeploymentData $deployment) => $deployment->status === 'failed')->count();
        $total = $successful + $failed;

        $sites = $scope !== 'site'
            ? $this->servers()->flatMap(fn (ServerData $server) => $this->sitesForServer($server->id))->keyBy('id')
            : collect();

        return [
            'summary' => [
                'successful' => $successful,
                'failed' => $failed,
                'successRate' => $total > 0 ? (int) round(($successful / $total) * 100) : 100,
                'periodDays' => 30,
            ],
            'recent' => $this->recentDeployments(5)->map(fn (DeploymentData $deployment) => [
                'site' => $sites->get($deployment->siteId)?->domain,
                'commitMessage' => $deployment->commitMessage,
                'commitHash' => $deployment->commitHash ? substr($deployment->commitHash, 0, 7) : null,
                'commitBranch' => $deployment->commitBranch,
                'status' => $deployment->statusLabel(),
                'health' => $deployment->health(),
                'duration' => $deployment->formattedDuration(),
                'when' => $deployment->createdAt?->diffForHumans(),
            ]),
            'showSiteColumn' => $scope !== 'site',
            'viewAllUrl' => ListDeployments::getUrl(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getAttentionItems(): array
    {
        $scope = $this->getScope();

        if ($scope !== 'site') {
            $items = [];

            foreach ($this->servers() as $server) {
                if (! $server->isOnline()) {
                    $items[] = "Server \"{$server->name}\" is not reporting a successful connection to Forge.";
                }
            }

            $lastDeployment = $this->recentDeployments(1)->first();

            if ($lastDeployment?->status === 'failed') {
                $items[] = 'The most recent deployment failed.';
            }

            return $items;
        }

        $items = [];

        [$server, $site] = $this->siteContext();
        $ssl = $this->sslCertificate();
        $lastDeployment = $this->recentDeployments(1)->first();

        if ($site && $site->status !== 'installed') {
            $items[] = 'The website is not currently online.';
        }

        if ($server && ! $server->isOnline()) {
            $items[] = 'The server is not reporting a successful connection to Forge.';
        }

        if ($ssl) {
            $daysRemaining = $ssl->estimatedDaysRemaining();

            if ($ssl->health() === HealthStatus::Critical && ! is_null($daysRemaining) && $daysRemaining <= 0) {
                $items[] = 'The SSL certificate has expired.';
            } elseif ($ssl->health() === HealthStatus::Attention && ! is_null($daysRemaining)) {
                $items[] = "SSL certificate expires in {$daysRemaining} day" . ($daysRemaining === 1 ? '' : 's') . '.';
            } elseif ($ssl->health() === HealthStatus::Critical) {
                $items[] = 'The SSL certificate request failed.';
            }
        } elseif ($site?->isSecured) {
            $items[] = 'No active SSL certificate was found for this website.';
        }

        if ($lastDeployment?->status === 'failed') {
            $items[] = 'The last deployment failed.';
        }

        return $items;
    }

    /**
     * @return array{0: ?ServerData, 1: ?SiteData}
     */
    protected function siteContext(): array
    {
        $server = $this->servers()->first();

        if (! $server) {
            return [null, null];
        }

        $site = $this->sitesForServer($server->id)->first();

        return [$server, $site];
    }

    protected function siteHealth(?ServerData $server, ?SiteData $site): HealthStatus
    {
        if (! $server || ! $site) {
            return HealthStatus::Unknown;
        }

        if ($site->status !== 'installed' || ! $server->isOnline()) {
            return HealthStatus::Critical;
        }

        if (! empty($this->getAttentionItems())) {
            return HealthStatus::Attention;
        }

        return HealthStatus::Healthy;
    }

    protected function sslCertificate(): ?SslCertificateData
    {
        if ($this->sslCertificateResolved) {
            return $this->memoizedSslCertificate;
        }

        $this->sslCertificateResolved = true;

        [$server, $site] = $this->siteContext();

        if (! $server || ! $site) {
            return $this->memoizedSslCertificate = null;
        }

        return $this->memoizedSslCertificate = app(SslRepositoryInterface::class)
            ->forSite($server->id, $site->id)
            ->sortByDesc(fn (SslCertificateData $certificate) => $certificate->active)
            ->first();
    }

    protected function sslStatusLabel(?SslCertificateData $ssl): string
    {
        if (! $ssl) {
            return 'Not available';
        }

        return match ($ssl->health()) {
            HealthStatus::Healthy => 'Valid',
            HealthStatus::Attention => 'Expiring soon',
            HealthStatus::Critical => 'Expired',
            HealthStatus::Unknown => $ssl->isInstalled() ? 'Valid' : 'Not installed',
        };
    }

    /**
     * @return array<int, string>
     */
    protected function sslCardLines(?SslCertificateData $ssl): array
    {
        if (! $ssl) {
            return ['No certificate found'];
        }

        $daysRemaining = $ssl->estimatedDaysRemaining();

        if (is_null($daysRemaining)) {
            return [str($ssl->type ?? 'certificate')->headline()->toString()];
        }

        return [$daysRemaining > 0 ? "{$daysRemaining} days left" : 'Expired'];
    }

    protected function servers(): Collection
    {
        return $this->memoizedServers ??= app(ServerRepositoryInterface::class)->all();
    }

    /**
     * The first call — for whichever server happens to be asked about
     * first — fetches every server's sites in one batch instead of one at
     * a time, so later calls for other servers (from this method or from
     * recentDeployments()'s own internal lookups) never need to fetch or
     * even re-check the cache again this render.
     *
     * @return Collection<int, SiteData>
     */
    protected function sitesForServer(int | string $serverId): Collection
    {
        if (! array_key_exists($serverId, $this->memoizedSitesByServer)) {
            $this->memoizedSitesByServer += app(SiteRepositoryInterface::class)
                ->allForServers($this->servers()->pluck('id'))
                ->all();
        }

        return $this->memoizedSitesByServer[$serverId] ?? collect();
    }

    protected function recentDeployments(int $limit): Collection
    {
        $this->memoizedDeployments ??= app(DeploymentRepositoryInterface::class)->recent(static::MAX_RECENT_DEPLOYMENTS);

        return $this->memoizedDeployments->take($limit);
    }
}
