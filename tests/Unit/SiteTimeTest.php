<?php

declare(strict_types=1);

use App\Support\SiteTime;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

test('display converts a UTC instant to the given timezone', function () {
    $utc = CarbonImmutable::create(2026, 1, 15, 12, 0, 0, 'UTC');

    $shown = SiteTime::display($utc, 'Asia/Kolkata');

    expect($shown->format('Y-m-d H:i'))->toBe('2026-01-15 17:30')
        ->and($shown->getTimezone()->getName())->toBe('Asia/Kolkata')
        ->and($utc->format('Y-m-d H:i'))->toBe('2026-01-15 12:00');
});

test('display respects a daylight-saving transition boundary', function () {
    // America/New_York springs forward on 2026-03-08 at 02:00 (EST -> EDT).
    $before = SiteTime::display(CarbonImmutable::create(2026, 3, 8, 6, 30, 0, 'UTC'), 'America/New_York');
    $after = SiteTime::display(CarbonImmutable::create(2026, 3, 8, 7, 30, 0, 'UTC'), 'America/New_York');

    expect($before->format('H:i'))->toBe('01:30')
        ->and($after->format('H:i'))->toBe('03:30');
});

test('parseLanguages trims, validates, and falls back to English', function () {
    expect(SiteTime::parseLanguages('hi,en'))->toBe(['hi', 'en'])
        ->and(SiteTime::parseLanguages(' hi , en '))->toBe(['hi', 'en'])
        ->and(SiteTime::parseLanguages(''))->toBe(['en'])
        ->and(SiteTime::parseLanguages(',,,'))->toBe(['en'])
        ->and(SiteTime::parseLanguages('en,xx!,toolongcode-land'))->toBe(['en'])
        ->and(SiteTime::parseLanguages('en-US,hi'))->toBe(['en-US', 'hi']);
});

test('display works under frozen time', function () {
    Carbon::setTestNow(Carbon::create(2026, 10, 9, 8, 0, 0, 'UTC'));

    try {
        $shown = SiteTime::display(Carbon::now(), 'Asia/Kolkata');

        expect($shown->format('Y-m-d H:i'))->toBe('2026-10-09 13:30');
    } finally {
        Carbon::setTestNow();
    }
});
