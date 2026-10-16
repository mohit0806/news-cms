<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Redirect;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

function tagUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('reporters create tags but cannot merge or delete', function () {
    $this->actingAs(tagUser('Reporter'))->get(route('tags.index'))->assertOk();

    $this->actingAs(tagUser('Reporter'))
        ->post(route('tags.store'), ['translations' => ['en' => ['name' => 'Climate', 'slug' => '']]])
        ->assertRedirect(route('tags.index'));

    expect(Term::where('vocabulary', 'tag')->count())->toBe(1);

    $tag = Term::where('vocabulary', 'tag')->firstOrFail();

    $this->actingAs(tagUser('Reporter'))->get(route('tags.merge'))->assertForbidden();
    $this->actingAs(tagUser('Reporter'))
        ->post(route('tags.merge.store'), ['source_id' => $tag->getKey(), 'target_id' => $tag->getKey()])
        ->assertForbidden();
    $this->actingAs(tagUser('Reporter'))->delete(route('tags.destroy', $tag))->assertForbidden();
});

test('merge re-points articles, records a 301, and removes the source', function () {
    $article = Article::factory()->create();
    $source = Term::factory()->tag()->translated('en', 'Global Warming')->create();
    $target = Term::factory()->tag()->translated('en', 'Climate Change')->create();

    DB::table('termables')->insert([
        'term_id' => $source->getKey(), 'termable_type' => Article::class,
        'termable_id' => $article->getKey(), 'sort' => 0,
    ]);

    $this->actingAs(tagUser('Editor'))
        ->post(route('tags.merge.store'), ['source_id' => $source->getKey(), 'target_id' => $target->getKey()])
        ->assertRedirect(route('tags.index'));

    expect(Term::whereKey($source->getKey())->exists())->toBeFalse()
        ->and(DB::table('termables')->where('term_id', $target->getKey())->count())->toBe(1)
        ->and(DB::table('termables')->where('term_id', $source->getKey())->count())->toBe(0);

    $redirect = Redirect::where('from_path', '/tag/global-warming')->firstOrFail();

    expect($redirect->to_url)->toBe('/tag/climate-change')
        ->and($redirect->status_code)->toBe(301);
});

test('merge skips duplicate assignments instead of violating uniqueness', function () {
    $article = Article::factory()->create();
    $source = Term::factory()->tag()->translated('en', 'Football')->create();
    $target = Term::factory()->tag()->translated('en', 'Soccer')->create();

    foreach ([$source, $target] as $tag) {
        DB::table('termables')->insert([
            'term_id' => $tag->getKey(), 'termable_type' => Article::class,
            'termable_id' => $article->getKey(), 'sort' => 0,
        ]);
    }

    $this->actingAs(tagUser('Editor'))
        ->post(route('tags.merge.store'), ['source_id' => $source->getKey(), 'target_id' => $target->getKey()])
        ->assertRedirect(route('tags.index'));

    expect(DB::table('termables')->where('term_id', $target->getKey())->count())->toBe(1);
});

test('merging a tag into itself is rejected', function () {
    $tag = Term::factory()->tag()->translated()->create();

    $this->actingAs(tagUser('Editor'))
        ->post(route('tags.merge.store'), ['source_id' => $tag->getKey(), 'target_id' => $tag->getKey()])
        ->assertSessionHasErrors('source_id');
});

test('tag slugs collide-suffix on duplicates', function () {
    Term::factory()->tag()->translated('en', 'Climate')->create();

    $this->actingAs(tagUser('Editor'))
        ->post(route('tags.store'), ['translations' => ['en' => ['name' => 'Climate', 'slug' => '']]])
        ->assertRedirect(route('tags.index'));

    expect(Term::where('vocabulary', 'tag')->count())->toBe(2);
});
