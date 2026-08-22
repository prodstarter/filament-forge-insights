@php
    /** @var array{successful: int, failed: int, successRate: int, periodDays: int} $summary */
    /** @var \Illuminate\Support\Collection $recent */
@endphp

<x-filament::section icon="heroicon-o-rocket-launch" heading="Recent Deployments">
    <x-slot name="description">
        <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ $summary['successful'] }} successful</span>
        ·
        <span @class(['font-medium text-rose-600 dark:text-rose-400' => $summary['failed'] > 0])>{{ $summary['failed'] }} failed</span>
        ·
        {{ $summary['successRate'] }}% success rate
        <span class="text-gray-400 dark:text-gray-500">(last {{ $summary['periodDays'] }} days)</span>
    </x-slot>

    <x-slot name="afterHeader">
        <x-filament::button tag="a" :href="$viewAllUrl" color="gray" size="sm" icon="heroicon-o-arrow-right" icon-position="after">
            View all deployments
        </x-filament::button>
    </x-slot>

    @if ($recent->isEmpty())
        <div class="flex flex-col items-center gap-2 py-8 text-center">
            <x-filament::icon icon="heroicon-o-rocket-launch" class="h-8 w-8 text-gray-300 dark:text-gray-600" />
            <p class="text-sm text-gray-500 dark:text-gray-400">No deployments yet</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-left text-xs font-medium uppercase tracking-wide text-gray-400 dark:border-white/5 dark:text-gray-500">
                        <th class="py-2 pe-3 font-medium">Commit</th>
                        @if ($showSiteColumn)
                            <th class="px-3 py-2 font-medium">Site</th>
                        @endif
                        <th class="px-3 py-2 font-medium">Branch</th>
                        <th class="px-3 py-2 font-medium">Status</th>
                        <th class="px-3 py-2 font-medium">Duration</th>
                        <th class="ps-3 py-2 text-right font-medium">Deployed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recent as $deployment)
                        <tr class="border-b border-gray-100 last:border-0 dark:border-white/5">
                            <td class="py-3 pe-3">
                                <p class="max-w-xs truncate font-medium text-gray-950 dark:text-white">{{ $deployment['commitMessage'] ?? '—' }}</p>
                                @if ($deployment['commitHash'])
                                    <p class="font-mono text-xs text-gray-400 dark:text-gray-500">{{ $deployment['commitHash'] }}</p>
                                @endif
                            </td>
                            @if ($showSiteColumn)
                                <td class="px-3 py-3 text-gray-500 dark:text-gray-400">{{ $deployment['site'] ?? '—' }}</td>
                            @endif
                            <td class="px-3 py-3">
                                <x-filament::badge color="gray">{{ $deployment['commitBranch'] ?? '—' }}</x-filament::badge>
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center gap-1.5 {{ $deployment['health']->textClasses() }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $deployment['health']->dotClasses() }}"></span>
                                    {{ $deployment['status'] ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-gray-500 dark:text-gray-400">{{ $deployment['duration'] }}</td>
                            <td class="ps-3 py-3 text-right text-gray-500 dark:text-gray-400">{{ $deployment['when'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament::section>
