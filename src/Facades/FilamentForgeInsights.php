<?php

namespace Prodstarter\FilamentForgeInsights\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Prodstarter\FilamentForgeInsights\FilamentForgeInsights
 */
class FilamentForgeInsights extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Prodstarter\FilamentForgeInsights\FilamentForgeInsights::class;
    }
}
