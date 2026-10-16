<?php

declare(strict_types=1);

namespace App\Settings;

class ContentSettings extends NewsSettings
{
    public bool $require_featured_image = true;

    public bool $require_alt_text = true;

    public int $articles_per_page = 20;

    public int $trash_retention_days = 30;

    public int $revision_count = 50;

    public int $breaking_default_hours = 2;

    public static function group(): string
    {
        return 'content';
    }

    public static function owner(): string
    {
        return 'Editorial';
    }

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'require_featured_image' => ['required', 'boolean'],
            'require_alt_text' => ['required', 'boolean'],
            'articles_per_page' => ['required', 'integer', 'min:1', 'max:100'],
            'trash_retention_days' => ['required', 'integer', 'min:0', 'max:365'],
            'revision_count' => ['required', 'integer', 'min:0', 'max:500'],
            'breaking_default_hours' => ['required', 'integer', 'min:1', 'max:72'],
        ];
    }
}
