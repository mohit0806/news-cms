<?php

declare(strict_types=1);

namespace App\Settings;

class LocaleSettings extends NewsSettings
{
    /** @var list<string> */
    public array $enabled_languages = ['en'];

    public static function group(): string
    {
        return 'locale';
    }

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'enabled_languages' => ['required', 'array', 'min:1'],
            'enabled_languages.*' => ['required', 'string', 'regex:/^[a-z]{2,3}(-[a-zA-Z]{2,8})?$/'],
        ];
    }
}
