<?php

declare(strict_types=1);

namespace App\Settings;

class AnalyticsSettings extends NewsSettings
{
    public bool $analytics_enabled = false;

    public float $trending_half_life_hours = 24.0;

    public static function group(): string
    {
        return 'analytics';
    }

    public static function owner(): string
    {
        return 'Analytics';
    }

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'analytics_enabled' => ['required', 'boolean'],
            'trending_half_life_hours' => ['required', 'numeric', 'min:1', 'max:720'],
        ];
    }
}
