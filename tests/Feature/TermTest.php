<?php

declare(strict_types=1);

use App\Models\Term;
use Illuminate\Database\QueryException;

test('terms nest to three levels', function () {
    $root = Term::factory()->translated()->create();
    $child = Term::factory()->translated()->create(['parent_id' => $root->getKey()]);
    $grandchild = Term::factory()->translated()->create(['parent_id' => $child->getKey()]);

    expect($root->depth())->toBe(1)
        ->and($child->depth())->toBe(2)
        ->and($grandchild->depth())->toBe(3)
        ->and($root->children)->toHaveCount(1);
});

test('translation slugs are unique per language', function () {
    $term = Term::factory()->translated('en', 'News')->create();
    $other = Term::factory()->create();

    // Same slug in another language is allowed.
    $other->translations()->create(['language' => 'hi', 'name' => 'News', 'slug' => 'news']);

    expect($other->translation('hi')?->slug)->toBe('news');

    // Same slug in the same language is rejected by the database.
    expect(fn () => $term->translations()->create(['language' => 'en', 'name' => 'News', 'slug' => 'news']))
        ->toThrow(QueryException::class);
});

test('translation falls back to the default language', function () {
    $term = Term::factory()->translated('en', 'News')->create();

    expect($term->translation('hi')?->name)->toBe('News')
        ->and($term->translation('en')?->name)->toBe('News')
        ->and(Term::factory()->create()->translation('en'))->toBeNull();
});

test('tags carry no hierarchy', function () {
    $tag = Term::factory()->tag()->translated()->create();

    expect($tag->vocabulary)->toBe('tag')
        ->and($tag->parent_id)->toBeNull()
        ->and($tag->depth())->toBe(1);
});
