<?php

// config for Prodstarter/FilamentForgeInsights
return [

    'token' => env('FORGE_API_TOKEN'),

    'organization' => env('FORGE_ORGANIZATION_ID'),

    /*
     * Optionally narrow everything the plugin displays down to a single
     * server, or a single site on that server. Leave both null to show
     * the whole organization. These are normally set via the Settings
     * page rather than here, but env fallbacks are supported for
     * deploy-time configuration.
     */
    'server' => env('FORGE_SERVER_ID'),

    'site' => env('FORGE_SITE_ID'),

    'base_url' => env('FORGE_API_BASE_URL', 'https://forge.laravel.com/api'),

    'navigation_group' => 'Infrastructure',

    /*
     * How long (in seconds) each resource's data is cached before it is
     * re-fetched from the Forge API. Overridable per-resource via the
     * plugin's ->cacheFor() method.
     */
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
