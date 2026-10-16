<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

function policyUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('reporters edit only their own drafts', function () {
    $reporter = policyUser('Reporter');
    $own = Article::factory()->create(['status' => 'draft', 'created_by' => $reporter->getKey()]);
    $other = Article::factory()->create(['status' => 'draft']);
    $ownPublished = Article::factory()->published()->create(['created_by' => $reporter->getKey()]);

    expect($reporter->can('update', $own))->toBeTrue()
        ->and($reporter->can('update', $other))->toBeFalse()
        ->and($reporter->can('update', $ownPublished))->toBeFalse()
        ->and($reporter->can('publish', Article::class))->toBeFalse()
        ->and($reporter->can('delete', Article::class))->toBeFalse();
});

test('reporters in review keep editing, editors edit anything', function () {
    $reporter = policyUser('Reporter');
    $review = Article::factory()->inReview()->create(['created_by' => $reporter->getKey()]);
    $editor = policyUser('Editor');
    $published = Article::factory()->published()->create();

    expect($reporter->can('update', $review))->toBeTrue()
        ->and($editor->can('update', $published))->toBeTrue()
        ->and($editor->can('publish', Article::class))->toBeTrue()
        ->and($editor->can('setBreaking', Article::class))->toBeTrue()
        ->and($reporter->can('setBreaking', Article::class))->toBeFalse();
});

test('contributors create but never publish or break', function () {
    $contributor = policyUser('Contributor');
    $draft = Article::factory()->create(['created_by' => $contributor->getKey()]);

    expect($contributor->can('create', Article::class))->toBeTrue()
        ->and($contributor->can('update', $draft))->toBeTrue()
        ->and($contributor->can('publish', Article::class))->toBeFalse()
        ->and($contributor->can('submit', $draft))->toBeTrue();
});

test('admins hold every article power except force delete', function () {
    $admin = policyUser('Admin');
    $article = Article::factory()->published()->create();

    expect($admin->can('update', $article))->toBeTrue()
        ->and($admin->can('publish', Article::class))->toBeTrue()
        ->and($admin->can('unpublish', Article::class))->toBeTrue()
        ->and($admin->can('delete', Article::class))->toBeTrue()
        ->and($admin->can('setBreaking', Article::class))->toBeTrue()
        ->and($admin->can('forceDelete', $article))->toBeFalse();
});

test('denials explain themselves', function () {
    $reporter = policyUser('Reporter');
    $published = Article::factory()->published()->create();

    $this->actingAs($reporter);
    $response = Gate::inspect('update', [$published]);

    expect($response->allowed())->toBeFalse()
        ->and((string) $response->message())->toContain('editors');
});
