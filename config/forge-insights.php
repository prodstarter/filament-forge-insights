<?php

// config for Prodstarter/FilamentForgeInsights
return [

    'token' => env('FORGE_API_TOKEN'),

    'organization' => env('FORGE_ORGANIZATION_ID'),

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
