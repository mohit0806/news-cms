<?php

declare(strict_types=1);

namespace App\Settings;

class SeoSettings extends NewsSettings
{
    public string $title_template = '{title} — {site}';

    public ?string $meta_description_fallback = null;

    public ?string $default_og_image = null;

    public string $robots_default = 'index,follow';

    public int $tag_index_min_articles = 5;

    public static function group(): string
    {
        return 'seo';
    }

    public static function owner(): string
    {
        return 'SEO';
    }

    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'title_template' => ['required', 'string', 'max:300'],
            'meta_description_fallback' => ['nullable', 'string', 'max:500'],
            'default_og_image' => ['nullable', 'string', 'max:500'],
            'robots_default' => ['required', 'string', 'max:100'],
            'tag_index_min_articles' => ['required', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
