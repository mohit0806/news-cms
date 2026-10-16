<?php

declare(strict_types=1);

use App\Actions\Articles\TransitionArticle;
use App\Models\Article;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
    Storage::fake('public');
});

function transitionUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

function publishableArticle(User $author, string $status = 'draft'): Article
{
    $article = Article::factory()->create([
        'status' => $status,
        'created_by' => $author->getKey(),
        'published_at' => now()->addHour(),
        'body_text' => 'Full body text for publishing.',
        'body_html' => '<p class="cms-block cms-block--paragraph">Full body text for publishing.</p>',
    ]);

    $path = tempnam(sys_get_temp_dir(), 'feat').'.jpg';
    $image = new Imagick;
    $image->newImage(40, 30, new ImagickPixel('red'));
    $image->setImageFormat('jpeg');
    $image->writeImage($path);
    $image->clear();

    $article->addMedia($path)
        ->withCustomProperties(['alt' => 'A news photo'])
        ->toMediaCollection('featured');

    return $article->refresh();
}

test('reporter submits, editor approves to scheduled, scheduler publishes', function () {
    $reporter = transitionUser('Reporter');
    $editor = transitionUser('Editor');
    $article = publishableArticle($reporter);

    $this->actingAs($reporter)->post(route('articles.submit', $article))->assertRedirect();
    expect($article->refresh()->status)->toBe('in_review');

    // Editors were notified of the submission.
    expect($editor->notifications()->count())->toBe(1)
        ->and($reporter->notifications()->count())->toBe(0);

    // A repeated submit is rejected as an invalid transition — and notifies nobody twice.
    $this->actingAs($reporter)->post(route('articles.submit', $article))->assertSessionHasErrors('status');
    expect($editor->notifications()->count())->toBe(1);

    // Schedule for the future.
    $future = now()->addHour()->format('Y-m-d\TH:i');

    $this->actingAs($editor)->post(route('articles.publish', $article), ['published_at' => $future])->assertRedirect();
    expect($article->refresh()->status)->toBe('scheduled');

    // Time passes; the due flip publishes.
    try {
        Carbon::setTestNow(now()->addHours(2));
        $published = TransitionArticle::publishDue();

        expect($published)->toBe([$article->getKey()])
            ->and($article->refresh()->status)->toBe('published');
    } finally {
        Carbon::setTestNow();
    }
});

test('rejection requires a note and notifies the author', function () {
    $reporter = transitionUser('Reporter');
    $editor = transitionUser('Editor');
    $article = publishableArticle($reporter, 'in_review');

    $this->actingAs($editor)
        ->post(route('articles.reject', $article), ['note' => ''])
        ->assertSessionHasErrors('note');

    $this->actingAs($editor)
        ->post(route('articles.reject', $article), ['note' => 'Needs a stronger lede.'])
        ->assertRedirect();

    expect($article->refresh()->status)->toBe('draft')
        ->and($article->review_note)->toBe('Needs a stronger lede.')
        ->and($reporter->notifications()->count())->toBe(1);
});

test('publish is blocked without required fields', function () {
    $editor = transitionUser('Editor');
    $bare = Article::factory()->create(['status' => 'draft']);

    $this->actingAs($editor)->post(route('articles.publish', $bare), ['published_at' => ''])->assertSessionHasErrors();

    expect($bare->refresh()->status)->toBe('draft');
});

test('publish requires a featured image with alt text when configured', function () {
    $editor = transitionUser('Editor');
    $article = Article::factory()->create(['status' => 'in_review', 'published_at' => now()]);

    $this->actingAs($editor)->post(route('articles.publish', $article), [])->assertSessionHasErrors('featured');

    $path = tempnam(sys_get_temp_dir(), 'feat').'.jpg';
    $image = new Imagick;
    $image->newImage(40, 30, new ImagickPixel('red'));
    $image->setImageFormat('jpeg');
    $image->writeImage($path);
    $image->clear();

    $article->addMedia($path)->toMediaCollection('featured');

    $this->actingAs($editor)->post(route('articles.publish', $article), [])->assertSessionHasErrors('featured');

    $article->getFirstMedia('featured')?->setCustomProperty('alt', 'Alt text');
    $article->getFirstMedia('featured')?->save();

    $this->actingAs($editor)->post(route('articles.publish', $article), [])->assertRedirect();

    expect($article->refresh()->status)->toBe('published');
});

test('illegal transitions and unauthorized publishes fail', function () {
    $reporter = transitionUser('Reporter');
    $editor = transitionUser('Editor');
    $published = Article::factory()->published()->create();

    $this->actingAs($editor)->post(route('articles.submit', $published))->assertSessionHasErrors('status');

    $draft = Article::factory()->create(['status' => 'draft']);

    $this->actingAs($reporter)->post(route('articles.publish', $draft), [])->assertForbidden();

    $unpublished = publishableArticle($editor);
    $unpublished->forceFill(['status' => 'unpublished'])->save();

    $this->actingAs($editor)->post(route('articles.publish', $unpublished), ['published_at' => now()->format('Y-m-d\TH:i')])
        ->assertRedirect();

    expect($unpublished->refresh()->status)->toBe('published');
});

test('unpublish and unschedule take content down', function () {
    $editor = transitionUser('Editor');
    $published = Article::factory()->published()->create();
    $scheduled = Article::factory()->scheduled(now()->addDay()->toDateTimeString())->create();

    $this->actingAs($editor)->post(route('articles.unpublish', $published))->assertRedirect();
    expect($published->refresh()->status)->toBe('unpublished');

    $this->actingAs($editor)->post(route('articles.unpublish', $scheduled))->assertRedirect();
    expect($scheduled->refresh()->status)->toBe('draft');
});

test('transitions are audited', function () {
    $reporter = transitionUser('Reporter');
    $editor = transitionUser('Editor');
    $article = publishableArticle($reporter);

    $this->actingAs($reporter)->post(route('articles.submit', $article))->assertRedirect();

    expect(DB::table('activity_log')->where('description', 'articles.submitted')->count())->toBe(1);

    $this->actingAs($editor)->post(route('articles.reject', $article), ['note' => 'Nope.'])->assertRedirect();

    expect(DB::table('activity_log')->where('description', 'articles.rejected')->count())->toBe(1);
});
