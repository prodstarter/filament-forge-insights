<?php

namespace Prodstarter\FilamentForgeInsights\Support;

/**
 * The plugin's consistent four-tier visual health language, used across
 * every card and badge on the dashboard so a client-facing viewer can scan
 * status without reading prose: green (healthy), orange (needs attention),
 * red (critical), gray (unknown / not available).
 */
enum HealthStatus: string
{
    case Healthy = 'healthy';
    case Attention = 'attention';
    case Critical = 'critical';
    case Unknown = 'unknown';

    /**
     * The Filament semantic color name for this status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Healthy => 'success',
            self::Attention => 'warning',
            self::Critical => 'danger',
            self::Unknown => 'gray',
        };
    }

    public function dotClasses(): string
    {
        return match ($this) {
            self::Healthy => 'bg-emerald-500',
            self::Attention => 'bg-amber-500',
            self::Critical => 'bg-rose-500',
            self::Unknown => 'bg-gray-400',
        };
    }

    public function textClasses(): string
    {
        return match ($this) {
            self::Healthy => 'text-emerald-600 dark:text-emerald-400',
            self::Attention => 'text-amber-600 dark:text-amber-400',
            self::Critical => 'text-rose-600 dark:text-rose-400',
            self::Unknown => 'text-gray-500 dark:text-gray-400',
        };
    }

    public function iconBackgroundClasses(): string
    {
        return match ($this) {
            self::Healthy => 'bg-emerald-50 dark:bg-emerald-500/10',
            self::Attention => 'bg-amber-50 dark:bg-amber-500/10',
            self::Critical => 'bg-rose-50 dark:bg-rose-500/10',
            self::Unknown => 'bg-gray-50 dark:bg-gray-500/10',
        };
    }

    public function iconClasses(): string
    {
        return match ($this) {
            self::Healthy => 'text-emerald-600 dark:text-emerald-400',
            self::Attention => 'text-amber-600 dark:text-amber-400',
            self::Critical => 'text-rose-600 dark:text-rose-400',
            self::Unknown => 'text-gray-400 dark:text-gray-500',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Healthy => 'Healthy',
            self::Attention => 'Needs attention',
            self::Critical => 'Critical',
            self::Unknown => 'Unknown',
        };
    }
}
