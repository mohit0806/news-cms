<?php

declare(strict_types=1);

namespace App\Settings;

class CacheSettings extends NewsSettings
{
    public int $ttl_article_seconds = 600;

    public int $ttl_listing_seconds = 120;

    public int $ttl_homepage_seconds = 60;

    public static function group(): string
    {
        return 'cache';
    }

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'ttl_article_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'ttl_listing_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'ttl_homepage_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
        ];
    }
}
