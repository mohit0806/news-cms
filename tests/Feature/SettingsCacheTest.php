<?php

declare(strict_types=1);

use App\Settings\ContentSettings;
use App\Settings\GeneralSettings;

test('saved settings are visible to fresh resolutions without stale reads', function () {
    $settings = app(ContentSettings::class);
    $settings->fill([
        'require_featured_image' => true,
        'require_alt_text' => true,
        'articles_per_page' => 20,
        'trash_retention_days' => 30,
        'revision_count' => 25,
        'breaking_default_hours' => 2,
    ]);
    $settings->save();

    expect(app(ContentSettings::class)->revision_count)->toBe(25);

    $fresh = app(ContentSettings::class);
    $fresh->fill([
        'require_featured_image' => true,
        'require_alt_text' => true,
        'articles_per_page' => 20,
        'trash_retention_days' => 30,
        'revision_count' => 50,
        'breaking_default_hours' => 2,
    ]);
    $fresh->save();

    expect(app(ContentSettings::class)->revision_count)->toBe(50);
});

test('language-scoped values persist per language', function () {
    $settings = app(GeneralSettings::class);
    $settings->fill([
        'site_name' => ['en' => 'News', 'hi' => 'समाचार'],
        'tagline' => [],
        'logo_light' => null,
        'logo_dark' => null,
        'favicon' => null,
        'default_language' => 'en',
        'display_timezone' => 'Asia/Kolkata',
    ]);
    $settings->save();

    expect(app(GeneralSettings::class)->site_name)->toBe(['en' => 'News', 'hi' => 'समाचार']);
});
