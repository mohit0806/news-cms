<?php

declare(strict_types=1);

namespace App\Settings;

class AppearanceSettings extends NewsSettings
{
    public string $active_theme = 'default';

    public static function group(): string
    {
        return 'appearance';
    }

    public static function owner(): string
    {
        return 'Theme';
    }

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'active_theme' => ['required', 'string', 'max:100'],
        ];
    }
}
