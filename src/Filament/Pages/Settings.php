<?php

namespace Prodstarter\FilamentForgeInsights\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
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
        return config('forge-insights.navigation_group', 'Infrastructure');
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
                    ->required(),
            ])
            ->statePath('data');
    }

    public function getSubheading(): string | Htmlable | null
    {
        return app(SettingsManager::class)->isConnected()
            ? 'Connected to Forge.'
            : 'Not connected to Forge.';
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (! $this->attemptConnection($data['token'], $data['organization'])) {
            return;
        }

        app(SettingsManager::class)->save($data['token'], $data['organization']);

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
            ->body('Infrastructure data will be refreshed the next time it is viewed.')
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
