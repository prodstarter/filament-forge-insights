<?php

namespace Prodstarter\FilamentForgeInsights\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Prodstarter\FilamentForgeInsights\Settings\SettingsManager;
use Prodstarter\FilamentForgeInsights\Support\ForgeCache;
use Throwable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class Settings extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::Cog6Tooth;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament-forge-insights::pages.settings';

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return config('forge-insights.navigation_group', 'Server');
    }

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $setting = app(SettingsManager::class)->current();

        $this->form->fill([
            'token' => $setting?->token,
            'organization' => $setting?->organization,
            'server' => $setting?->server,
            'site' => $setting?->site,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('token')
                    ->label('Forge API Token')
                    ->password()
                    ->revealable()
                    ->live(onBlur: true)
                    ->required(),
                Select::make('organization')
                    ->label('Organization')
                    ->native(false)
                    ->options(fn (Get $get): array => $this->organizationOptions($get('token')))
                    ->helperText('Enter your API token above to load your organizations.')
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('server', null))
                    ->required(),
                Select::make('server')
                    ->label('Server')
                    ->native(false)
                    ->options(fn (Get $get): array => $this->serverOptions($get('token'), $get('organization')))
                    ->helperText('Only show this one server, instead of the whole organization. Leave blank to show every server.')
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('site', null))
                    ->visible(fn (Get $get): bool => filled($get('organization'))),
                Select::make('site')
                    ->label('Site')
                    ->native(false)
                    ->options(fn (Get $get): array => $this->siteOptions($get('token'), $get('organization'), $get('server')))
                    ->helperText('Only show this one site, instead of every site on the server. Useful when a portal belongs to a single client whose site shares a server with others.')
                    ->visible(fn (Get $get): bool => filled($get('server'))),
            ])
            ->statePath('data');
    }

    public function getSubheading(): string | Htmlable | null
    {
        $manager = app(SettingsManager::class);

        if (! $manager->isConnected()) {
            return 'Not connected to Forge.';
        }

        return match (true) {
            $manager->isScopedToSite() => 'Connected to Forge, showing a single site.',
            $manager->isScopedToServer() => 'Connected to Forge, showing a single server.',
            default => 'Connected to Forge, showing the whole organization.',
        };
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (! $this->attemptConnection($data['token'], $data['organization'])) {
            return;
        }

        app(SettingsManager::class)->save(
            $data['token'],
            $data['organization'],
            $data['server'] ?? null,
            $data['site'] ?? null,
        );

        ForgeCache::flush();

        Notification::make()
            ->title('Connected to Forge')
            ->success()
            ->send();
    }

    public function testConnection(): void
    {
        $data = $this->form->getState();

        if ($this->attemptConnection($data['token'], $data['organization'])) {
            Notification::make()
                ->title('Connection successful')
                ->success()
                ->send();
        }
    }

    public function sync(): void
    {
        ForgeCache::flush();

        Notification::make()
            ->title('Cache cleared')
            ->body('Server data will be refreshed the next time it is viewed.')
            ->success()
            ->send();
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sync Now')
                ->color('gray')
                ->icon(Heroicon::ArrowPath)
                ->action('sync')
                ->visible(fn (): bool => app(SettingsManager::class)->isConnected()),
            Action::make('testConnection')
                ->label('Test Connection')
                ->color('gray')
                ->action('testConnection'),
            Action::make('save')
                ->label('Save')
                ->action('save'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function organizationOptions(?string $token): array
    {
        if (blank($token)) {
            return [];
        }

        try {
            return app(SettingsManager::class)
                ->listOrganizations($token)
                ->pluck('name', 'slug')
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, string>
     */
    protected function serverOptions(?string $token, ?string $organization): array
    {
        if (blank($token) || blank($organization)) {
            return [];
        }

        try {
            return app(SettingsManager::class)
                ->listServers($token, $organization)
                ->pluck('name', 'id')
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, string>
     */
    protected function siteOptions(?string $token, ?string $organization, ?string $server): array
    {
        if (blank($token) || blank($organization) || blank($server)) {
            return [];
        }

        try {
            return app(SettingsManager::class)
                ->listSites($token, $organization, $server)
                ->pluck('name', 'id')
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    protected function attemptConnection(?string $token, ?string $organization): bool
    {
        try {
            if (app(SettingsManager::class)->testConnection($token, $organization)) {
                return true;
            }
        } catch (Throwable) {
            //
        }

        Notification::make()
            ->title('Could not connect to Forge')
            ->body('Check the API token and organization, then try again.')
            ->danger()
            ->send();

        return false;
    }
}
