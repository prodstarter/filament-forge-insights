<?php

namespace Prodstarter\FilamentForgeInsights\Commands;

use Illuminate\Console\Command;

class FilamentForgeInsightsCommand extends Command
{
    public $signature = 'filament-forge-insights';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
