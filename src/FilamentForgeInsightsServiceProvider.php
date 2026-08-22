<?php

namespace Prodstarter\FilamentForgeInsights;

use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Prodstarter\FilamentForgeInsights\Commands\FilamentForgeInsightsCommand;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\DatabaseRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\DeploymentRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ScheduledJobRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\ServerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SiteRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\SslRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\Contracts\WorkerRepositoryInterface;
use Prodstarter\FilamentForgeInsights\Repositories\DatabaseRepository;
use Prodstarter\FilamentForgeInsights\Repositories\DeploymentRepository;
use Prodstarter\FilamentForgeInsights\Repositories\ScheduledJobRepository;
use Prodstarter\FilamentForgeInsights\Repositories\ServerRepository;
use Prodstarter\FilamentForgeInsights\Repositories\SiteRepository;
use Prodstarter\FilamentForgeInsights\Repositories\SslRepository;
use Prodstarter\FilamentForgeInsights\Repositories\WorkerRepository;
use Prodstarter\FilamentForgeInsights\Testing\TestsFilamentForgeInsights;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentForgeInsightsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-forge-insights';

    public static string $viewNamespace = 'filament-forge-insights';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('prodstarter/filament-forge-insights');
            });

        if (file_exists($package->basePath('/../config/forge-insights.php'))) {
            $package->hasConfigFile('forge-insights');
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations())
                ->runsMigrations();
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        $this->app->bind(ServerRepositoryInterface::class, ServerRepository::class);
        $this->app->bind(SiteRepositoryInterface::class, SiteRepository::class);
        $this->app->bind(DeploymentRepositoryInterface::class, DeploymentRepository::class);
        $this->app->bind(SslRepositoryInterface::class, SslRepository::class);
        $this->app->bind(ScheduledJobRepositoryInterface::class, ScheduledJobRepository::class);
        $this->app->bind(WorkerRepositoryInterface::class, WorkerRepository::class);
        $this->app->bind(DatabaseRepositoryInterface::class, DatabaseRepository::class);
    }

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-forge-insights/{$file->getFilename()}"),
                ], 'filament-forge-insights-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsFilamentForgeInsights);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'prodstarter/filament-forge-insights';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            Css::make('filament-forge-insights-styles', __DIR__ . '/../resources/dist/filament-forge-insights.css'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            FilamentForgeInsightsCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            '2026_07_20_000000_create_forge_insights_settings_table',
            '2026_07_21_000000_add_server_and_site_to_forge_insights_settings_table',
        ];
    }
}
