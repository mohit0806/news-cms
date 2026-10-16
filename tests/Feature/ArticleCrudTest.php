<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Author;
use App\Models\Revision;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

function crudUser(string $role = 'Admin'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function articlePayload(array $overrides = []): array
{
    $category = Term::query()->where('vocabulary', 'category')->first()
        ?? Term::factory()->translated()->create();
    $author = Author::query()->first() ?? Author::factory()->create();

    return array_replace([
        'language' => 'en',
        'title' => 'Council Votes On Budget',
        'subtitle' => '',
        'slug' => '',
        'summary' => 'A short summary.',
        'body' => json_encode(['schema' => 1, 'type' => 'doc', 'content' => [
            ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Body text here.']]],
        ]]),
        'primary_category_id' => $category->getKey(),
        'author_id' => $author->getKey(),
        'dateline' => '',
        'source' => '',
        'is_breaking' => '0',
    ], $overrides);
}

test('reporters create drafts but cannot touch others', function () {
    $this->actingAs(crudUser('Reporter'))->get(route('articles.index'))->assertOk();

    $this->actingAs(crudUser('Reporter'))
        ->post(route('articles.store'), articlePayload())
        ->assertRedirect();

    expect(Article::where('title', 'Council Votes On Budget')->exists())->toBeTrue();

    $other = Article::factory()->create(['status' => 'draft']);

    $this->actingAs(crudUser('Reporter'))->get(route('articles.edit', $other))->assertForbidden();
});

test('guests are redirected from article routes', function () {
    $this->get(route('articles.index'))->assertRedirect(route('login'));
    $this->get(route('articles.create'))->assertRedirect(route('login'));
});

test('caps on relations are enforced', function () {
    $tags = Term::factory()->tag()->count(16)->translated()->create();

    $this->actingAs(crudUser())
        ->post(route('articles.store'), articlePayload(['tags' => $tags->pluck('id')->all()]))
        ->assertSessionHasErrors('tags');

    expect(Article::where('title', 'Council Votes On Budget')->exists())->toBeFalse();
});

test('stale saves block with a conflict message instead of overwriting', function () {
    $editor = crudUser('Editor');
    $article = Article::factory()->create(['status' => 'draft', 'title' => 'Original']);

    $response = $this->actingAs($editor)->put(
        route('articles.update', $article),
        articlePayload(['title' => 'Stale write', 'expected_row_version' => 999])
    );

    $response->assertSessionHasErrors('conflict');

    expect($article->refresh()->title)->toBe('Original')
        ->and($article->row_version)->toBe(1);
});

test('fresh saves bump the version and record manual revisions', function () {
    $editor = crudUser('Editor');
    $article = Article::factory()->create(['status' => 'draft', 'title' => 'Original']);

    $this->actingAs($editor)->put(
        route('articles.update', $article),
        articlePayload(['title' => 'Fresh write', 'expected_row_version' => 1])
    )->assertRedirect();

    expect($article->refresh()->title)->toBe('Fresh write')
        ->and($article->row_version)->toBe(2)
        ->and(Revision::where('subject_type', $article->getMorphClass())
            ->where('subject_id', $article->getKey())
            ->where('kind', 'manual')->count())->toBe(1);
});

test('autosave writes revisions without touching the row', function () {
    $editor = crudUser('Editor');
    $article = Article::factory()->create(['status' => 'draft', 'title' => 'Original']);

    $this->actingAs($editor)->post(
        route('articles.autosave', $article),
        articlePayload(['title' => 'Autosaved title'])
    )->assertOk()->assertJsonPath('saved_at', fn (): bool => true);

    expect($article->refresh()->title)->toBe('Original')
        ->and($article->row_version)->toBe(1)
        ->and(Revision::where('subject_type', $article->getMorphClass())
            ->where('subject_id', $article->getKey())
            ->where('kind', 'autosave')->count())->toBe(1);

    // A second autosave keeps exactly one autosave revision.
    $this->actingAs($editor)->post(
        route('articles.autosave', $article),
        articlePayload(['title' => 'Autosaved again'])
    )->assertOk();

    expect(Revision::where('subject_type', $article->getMorphClass())
        ->where('subject_id', $article->getKey())
        ->where('kind', 'autosave')->count())->toBe(1);
});

test('invalid bodies are rejected with messages', function () {
    $this->actingAs(crudUser())
        ->post(route('articles.store'), articlePayload(['body' => '{"schema":1,"type":"doc","content":[{"type":"evil"}]}']))
        ->assertSessionHasErrors('body');

    $this->actingAs(crudUser())
        ->post(route('articles.store'), articlePayload(['body' => 'not json']))
        ->assertSessionHasErrors('body');
});

test('media ids from blocks land in the usage index', function () {
    $this->actingAs(crudUser())->post(route('articles.store'), articlePayload([
        'body' => json_encode(['schema' => 1, 'type' => 'doc', 'content' => [
            ['type' => 'image', 'attrs' => ['media_id' => 42, 'alt' => 'Pic']],
        ]]),
    ]))->assertRedirect();

    $article = Article::where('title', 'Council Votes On Budget')->firstOrFail();

    expect(DB::table('media_usages')->where('usable_type', $article->getMorphClass())
        ->where('usable_id', $article->getKey())->pluck('media_id')->all())->toBe([42]);
});

test('trash and restore round-trip', function () {
    $article = Article::factory()->create();

    $this->actingAs(crudUser())->delete(route('articles.destroy', $article))->assertRedirect();

    expect(Article::whereKey($article->getKey())->exists())->toBeFalse();

    $this->actingAs(crudUser())->post(route('articles.restore', $article->getKey()))->assertRedirect();

    expect(Article::whereKey($article->getKey())->exists())->toBeTrue();
});
