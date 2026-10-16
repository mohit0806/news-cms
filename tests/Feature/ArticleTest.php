<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Author;
use App\Models\Term;
use Database\Seeders\CorpusSeeder;
use Illuminate\Database\QueryException;

test('articles persist with schema defaults', function () {
    $article = Article::factory()->create()->refresh();

    expect($article->status)->toBe('draft')
        ->and($article->row_version)->toBe(1)
        ->and($article->type)->toBe('standard')
        ->and($article->allow_indexing)->toBeTrue()
        ->and($article->body_json)->toBeArray();
});

test('slugs are unique per language', function () {
    Article::factory()->create(['language' => 'en', 'slug' => 'big-story']);

    // Same slug in another language is allowed.
    $hindi = Article::factory()->create(['language' => 'hi', 'slug' => 'big-story']);

    expect($hindi->slug)->toBe('big-story');

    expect(fn () => Article::factory()->create(['language' => 'en', 'slug' => 'big-story']))
        ->toThrow(QueryException::class);
});

test('published scope filters by status and date', function () {
    Article::factory()->published()->create();
    Article::factory()->create(['status' => 'draft', 'published_at' => now()->subDay()]);
    Article::factory()->scheduled(now()->addHour()->toDateTimeString());

    expect(Article::published()->count())->toBe(1);
});

test('language and category scopes filter correctly', function () {
    $category = Term::factory()->translated()->create();

    Article::factory()->create(['language' => 'hi', 'primary_category_id' => $category->getKey()]);
    Article::factory()->create(['language' => 'en']);

    expect(Article::inLanguage('hi')->count())->toBe(1)
        ->and(Article::inCategory($category->getKey())->count())->toBe(1);
});

test('co-authors and related articles attach with ordering', function () {
    $article = Article::factory()->create();
    $co = Author::factory()->create();
    $related = Article::factory()->create();

    $article->coAuthors()->attach($co->getKey(), ['sort' => 1]);
    $article->relatedArticles()->attach($related->getKey(), ['sort' => 2]);

    expect($article->coAuthors)->toHaveCount(1)
        ->and($article->relatedArticles)->toHaveCount(1)
        ->and($article->relatedArticles->first()->pivot->sort)->toBe(2);
});

test('soft-deleted articles leave the published scope', function () {
    $article = Article::factory()->published()->create();

    expect(Article::published()->count())->toBe(1);

    $article->delete();

    expect(Article::published()->count())->toBe(0)
        ->and(Article::withTrashed()->whereKey($article->getKey())->exists())->toBeTrue();
});

test('corpus seeder plants the labelled set idempotently', function () {
    $this->artisan('db:seed', ['--class' => CorpusSeeder::class])->assertSuccessful();
    $this->artisan('db:seed', ['--class' => CorpusSeeder::class])->assertSuccessful();

    expect(Article::where('language', 'hi')->count())->toBe(6)
        ->and(Article::where('language', 'en')->count())->toBe(6);
});
