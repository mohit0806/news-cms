<?php

declare(strict_types=1);

namespace App\Settings;

class SocialSettings extends NewsSettings
{
    /** @var array<string, string> */
    public array $profiles = [];

    public static function group(): string
    {
        return 'social';
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
            'profiles' => ['array'],
            'profiles.*' => ['nullable', 'url', 'max:500'],
        ];
    }
}
