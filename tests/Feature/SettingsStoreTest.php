<?php

declare(strict_types=1);

use App\Settings\AnalyticsSettings;
use App\Settings\AppearanceSettings;
use App\Settings\CacheSettings;
use App\Settings\ContactSettings;
use App\Settings\ContentSettings;
use App\Settings\GeneralSettings;
use App\Settings\LocaleSettings;
use App\Settings\SeoSettings;
use App\Settings\SocialSettings;
use Illuminate\Support\Facades\Validator;

test('every settings group has a unique group name and an owner', function () {
    $classes = [
        GeneralSettings::class, ContactSettings::class, SocialSettings::class,
        SeoSettings::class, ContentSettings::class, AppearanceSettings::class,
        AnalyticsSettings::class, LocaleSettings::class, CacheSettings::class,
    ];

    $groups = array_map(fn ($class) => $class::group(), $classes);

    expect($groups)->toHaveCount(9);
    expect(array_unique($groups))->toHaveCount(9);

    foreach ($classes as $class) {
        expect($class::owner())->toBeString()->not->toBeEmpty();
        expect($class::rules())->toBeArray()->not->toBeEmpty();
    }
});

test('settings resolve with documented defaults on a fresh database', function () {
    expect(app(GeneralSettings::class)->default_language)->toBe('en')
        ->and(app(ContentSettings::class)->articles_per_page)->toBe(20)
        ->and(app(ContentSettings::class)->trash_retention_days)->toBe(30)
        ->and(app(ContentSettings::class)->revision_count)->toBe(50)
        ->and(app(SeoSettings::class)->tag_index_min_articles)->toBe(5)
        ->and(app(AppearanceSettings::class)->active_theme)->toBe('default')
        ->and(app(AnalyticsSettings::class)->analytics_enabled)->toBeFalse()
        ->and(app(LocaleSettings::class)->enabled_languages)->toBe(['en'])
        ->and(app(CacheSettings::class)->ttl_article_seconds)->toBe(600);
});

test('settings persist and reload across resolutions', function () {
    // The package requires every property explicitly set before save.
    $settings = app(ContentSettings::class);
    $settings->fill([
        'require_featured_image' => true,
        'require_alt_text' => true,
        'articles_per_page' => 12,
        'trash_retention_days' => 30,
        'revision_count' => 50,
        'breaking_default_hours' => 2,
    ]);
    $settings->save();

    expect(app(ContentSettings::class)->articles_per_page)->toBe(12);
});

test('settings validation rules reject invalid values', function () {
    $validator = Validator::make(
        ['articles_per_page' => 0, 'tagline' => 'x'],
        [...ContentSettings::rules(), ...GeneralSettings::rules()]
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('articles_per_page'))->toBeTrue();
});

test('typed properties reject wrong PHP types', function () {
    $settings = app(ContentSettings::class);

    expect(fn () => $settings->articles_per_page = 'twelve')->toThrow(TypeError::class);
});
