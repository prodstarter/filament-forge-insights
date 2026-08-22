<x-filament::section>
    <div class="flex flex-col items-center gap-3 py-8 text-center">
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-50 dark:bg-gray-500/10">
            <x-filament::icon icon="heroicon-o-server-stack" class="h-6 w-6 text-gray-400" />
        </span>
        <div>
            <p class="text-sm font-semibold text-gray-950 dark:text-white">Not connected to Forge</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Connect a Forge account to see your server health here.</p>
        </div>
        <x-filament::button tag="a" :href="\Prodstarter\FilamentForgeInsights\Filament\Pages\Settings::getUrl()" icon="heroicon-o-cog-6-tooth">
            Go to Settings
        </x-filament::button>
    </div>
</x-filament::section>
