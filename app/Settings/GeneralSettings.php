<?php

declare(strict_types=1);

namespace App\Settings;

class GeneralSettings extends NewsSettings
{
    /** @var array<string, string> */
    public array $site_name = [];

    /** @var array<string, string> */
    public array $tagline = [];

    public ?string $logo_light = null;

    public ?string $logo_dark = null;

    public ?string $favicon = null;

    public string $default_language = 'en';

    public string $display_timezone = 'Asia/Kolkata';

    public static function group(): string
    {
        return 'general';
    }

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'site_name' => ['required', 'array'],
            'site_name.*' => ['required', 'string', 'max:200'],
            'tagline' => ['array'],
            'tagline.*' => ['nullable', 'string', 'max:300'],
            'logo_light' => ['nullable', 'string', 'max:500'],
            'logo_dark' => ['nullable', 'string', 'max:500'],
            'favicon' => ['nullable', 'string', 'max:500'],
            'default_language' => ['required', 'string', 'regex:/^[a-z]{2,3}(-[a-zA-Z]{2,8})?$/'],
            'display_timezone' => ['required', 'string', 'timezone'],
        ];
    }
}
