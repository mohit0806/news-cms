<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\SiteTime;

test('application timezone is UTC and site timezone defaults to Asia/Kolkata', function () {
    expect(config('app.timezone'))->toBe('UTC')
        ->and(SiteTime::siteTimezone())->toBe('Asia/Kolkata')
        ->and(SiteTime::display(now())->getTimezone()->getName())->toBe('Asia/Kolkata');
});

test('default locale is English with English fallback', function () {
    expect(config('app.locale'))->toBe('en')
        ->and(config('app.fallback_locale'))->toBe('en');
});

test('site languages default to English only', function () {
    expect(config('app.site_languages'))->toBe(['en']);
});

test('model timestamps round-trip in UTC', function () {
    $user = User::factory()->create();

    expect($user->created_at->getTimezone()->getName())->toBe('UTC');
});
