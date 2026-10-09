<?php

declare(strict_types=1);

// Smoke test for the P0-T07 slug corpora (representative samples; P2 expands them).

test('hindi slug corpus loads with the expected shape', function () {
    $corpus = require __DIR__.'/../Fixtures/slug_corpus_hi.php';

    expect($corpus)->toBeArray()->not->toBeEmpty();

    foreach ($corpus as $row) {
        expect($row)->toHaveKeys(['title', 'script', 'desk']);
        expect($row['script'])->toBe('hi');
        expect($row['title'])->toBeString()->not->toBeEmpty();
    }
});

test('latin slug corpus loads with the expected shape', function () {
    $corpus = require __DIR__.'/../Fixtures/slug_corpus_lat.php';

    expect($corpus)->toBeArray()->not->toBeEmpty();

    foreach ($corpus as $row) {
        expect($row)->toHaveKeys(['title', 'script', 'desk']);
        expect($row['script'])->toBe('lat');
        expect($row['title'])->toBeString()->not->toBeEmpty();
    }
});
