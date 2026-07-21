# Filament Forge Insights

[![Latest Version on Packagist](https://img.shields.io/packagist/v/prodstarter/filament-forge-insights.svg?style=flat-square)](https://packagist.org/packages/prodstarter/filament-forge-insights)
[![Tests](https://img.shields.io/github/actions/workflow/status/prodstarter/filament-forge-insights/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/prodstarter/filament-forge-insights/actions/workflows/tests.yml)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/prodstarter/filament-forge-insights/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/prodstarter/filament-forge-insights/actions/workflows/phpstan.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/prodstarter/filament-forge-insights.svg?style=flat-square)](https://packagist.org/packages/prodstarter/filament-forge-insights)

A read-only [Laravel Forge](https://forge.laravel.com) integration for Filament. Give agencies, freelancers, and internal teams a client-safe infrastructure dashboard — servers, sites, deployments, SSL, scheduled jobs, queue workers, and databases — without ever handing out real Forge access.

Connect a Forge API token once, and everything renders from live (cached) Forge data through a Saloon-based API layer. There are no write endpoints anywhere in the plugin — it cannot deploy, restart, or reconfigure anything on your servers.

## Requirements

- PHP 8.3+
- Laravel 11, 12, or 13
- Filament 5

## Installation

Install the package via Composer:

```bash
composer require prodstarter/filament-forge-insights
```

The plugin ships its own settings table and registers its migration automatically — just run your normal migration command after installing:

```bash
php artisan migrate
```

Register the plugin on a panel, in your panel provider's `panel()` method:

```php
use Prodstarter\FilamentForgeInsights\FilamentForgeInsightsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugins([
            FilamentForgeInsightsPlugin::make(),
        ]);
}
```

That's it — a new **Server** navigation group appears in the panel with a **Settings** page for connecting a Forge account.

### Connecting a Forge account

1. Generate an API token from your [Forge account settings](https://forge.laravel.com/profile/api).
2. In the panel, go to **Server → Settings**.
3. Paste the token, pick an **Organization**, then click **Test Connection** and **Save**.

Everything else under the Server nav group (Dashboard, Servers, Sites, Deployments, SSL, Databases, Jobs, Workers) populates automatically once connected.

### Scoping to a server or site

Agencies often run several clients' sites on one shared Forge server. The Settings page lets you narrow what a panel shows, in addition to the Organization:

- **Server** (optional) — show only this one server instead of the whole organization.
- **Site** (optional, requires a Server) — show only this one site on that server.

When scoped down to a single site, the Dashboard, Servers, Sites, Deployments, and SSL pages narrow to match. Scheduled Jobs, Workers, and Databases are server-wide resources in Forge's API (they can't be attributed to a single site), so those pages are hidden entirely when a portal is scoped to one site among several on a shared server — this avoids surfacing another client's cron commands or database names.

## Configuration

Publish the config file if you want to set connection defaults via environment variables (useful for CI or non-interactive deploys) instead of the Settings page:

```bash
php artisan vendor:publish --tag="filament-forge-insights-config"
```

```php
return [
    'token' => env('FORGE_API_TOKEN'),
    'organization' => env('FORGE_ORGANIZATION_ID'),
    'server' => env('FORGE_SERVER_ID'),
    'site' => env('FORGE_SITE_ID'),
    'base_url' => env('FORGE_API_BASE_URL', 'https://forge.laravel.com/api'),
    'navigation_group' => 'Server',
    'cache' => [
        'servers' => 600,
        'sites' => 600,
        'deployments' => 120,
        'ssl' => 1800,
        'jobs' => 600,
        'workers' => 600,
        'databases' => 600,
    ],
];
```

Anything saved on the Settings page takes precedence over these values at runtime — the config file is a fallback, not the primary way most users will connect an account.

The plugin can also be configured fluently, which is handy for setting an agency-wide default that the Settings page can still override:

```php
FilamentForgeInsightsPlugin::make()
    ->token(env('FORGE_API_TOKEN'))
    ->organization('my-organization-slug')
    ->navigationGroup('Infrastructure')
    ->cacheFor(now()->addMinutes(5));
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Stanley Ojadovwa](https://github.com/prodstarter)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
