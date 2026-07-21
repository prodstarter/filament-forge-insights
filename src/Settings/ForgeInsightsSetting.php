<?php

namespace Prodstarter\FilamentForgeInsights\Settings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ?string $token
 * @property ?string $organization
 * @property ?string $server
 * @property ?string $site
 * @property ?Carbon $connected_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ForgeInsightsSetting extends Model
{
    protected $table = 'forge_insights_settings';

    protected $fillable = [
        'token',
        'organization',
        'server',
        'site',
        'connected_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'connected_at' => 'datetime',
        ];
    }
}
