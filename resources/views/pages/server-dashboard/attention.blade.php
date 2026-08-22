@if (empty($items))
    <div class="flex items-center gap-3 rounded-xl bg-emerald-50 px-4 py-3 ring-1 ring-emerald-600/10 dark:bg-emerald-500/10 dark:ring-emerald-400/20">
        <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
        <div>
            <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Everything looks good</p>
            <p class="text-xs text-emerald-700/80 dark:text-emerald-400/80">Website, SSL and recent deployments are healthy.</p>
        </div>
    </div>
@else
    <div class="rounded-xl bg-amber-50 px-4 py-3 ring-1 ring-amber-600/10 dark:bg-amber-500/10 dark:ring-amber-400/20">
        <div class="flex items-center gap-2">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" />
            <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                {{ count($items) }} item{{ count($items) === 1 ? '' : 's' }} need{{ count($items) === 1 ? 's' : '' }} attention
            </p>
        </div>
        <ul class="mt-2 ml-7 list-disc space-y-1 text-xs text-amber-700/90 dark:text-amber-400/90">
            @foreach ($items as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </div>
@endif
