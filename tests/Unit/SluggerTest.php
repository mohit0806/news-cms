<?php

declare(strict_types=1);

use App\Support\Slugger;

test('transliterates devanagari titles to readable latin', function () {
    expect(Slugger::slug('नमस्ते दुनिया'))->toBe('namaste-duniya');
});

test('lowercases, hyphenates, and strips punctuation', function () {
    expect(Slugger::slug('  Big Story: MPs Debate @ 8pm!  '))->toBe('big-story-mps-debate-8pm');
});

test('trims to 80 characters at a word boundary', function () {
    $slug = Slugger::slug(str_repeat('word ', 30));

    expect($slug)->toHaveLength(79)
        ->and(mb_substr($slug, -1, 1))->not->toBe('-')
        ->and($slug)->toBe(implode('-', array_fill(0, 16, 'word')));
});

test('appends collision suffixes until free', function () {
    $taken = ['hello-world', 'hello-world-2'];

    $unique = Slugger::unique('hello-world', fn (string $candidate): bool => in_array($candidate, $taken, true));

    expect($unique)->toBe('hello-world-3')
        ->and(Slugger::unique('fresh-slug', fn (): bool => false))->toBe('fresh-slug');
});

test('unicode mode keeps the native script', function () {
    $slug = Slugger::slugUnicode('नमस्ते दुनिया');

    expect($slug)->toContain('नमस्ते')
        ->and(mb_strlen($slug))->toBeLessThanOrEqual(80);
});

test('unicode truncation counts characters, not bytes', function () {
    $slug = Slugger::slugUnicode(trim(str_repeat('नमस्ते ', 20)));

    expect(mb_strlen($slug))->toBeLessThanOrEqual(80)
        ->and(mb_check_encoding($slug, 'UTF-8'))->toBeTrue();
});

test('dispatches on the explicit mode flag', function () {
    // The config-backed default (`app.unicode_slugs`) is exercised through
    // the admin UI tests; unit scope covers the explicit flag only because
    // pure unit tests boot no container.
    expect(Slugger::for('नमस्ते', true))->toContain('नमस्ते')
        ->and(Slugger::for('नमस्ते', false))->toBe('namaste');
});
