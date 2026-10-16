<?php

declare(strict_types=1);

use App\Models\Revision;
use App\Models\User;
use App\Support\RevisionStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RevisionFixture extends Model
{
    protected $table = 'revision_fixtures';

    protected $fillable = ['title'];

    public $timestamps = false;
}

beforeEach(function () {
    Schema::create('revision_fixtures', function (Blueprint $table) {
        $table->id();
        $table->string('title')->nullable();
    });
});

afterEach(function () {
    Schema::dropIfExists('revision_fixtures');
});

test('records a manual revision with snapshot and user', function () {
    $user = User::factory()->create();
    $fixture = RevisionFixture::create(['title' => 'First']);

    $revision = RevisionStore::record($fixture, 'manual', ['title' => 'First'], $user->id);

    expect($revision->kind)->toBe('manual')
        ->and($revision->snapshot_json)->toBe(['title' => 'First'])
        ->and($revision->user_id)->toBe($user->id)
        ->and(Revision::query()->where('subject_type', $fixture->getMorphClass())->count())->toBe(1);
});

test('rejects an unknown revision kind', function () {
    $fixture = RevisionFixture::create(['title' => 'First']);

    expect(fn () => RevisionStore::record($fixture, 'bogus', []))->toThrow(InvalidArgumentException::class);
});

test('restore records the pre-restore state and applies the snapshot', function () {
    $fixture = RevisionFixture::create(['title' => 'v2']);
    $old = RevisionStore::record($fixture, 'manual', ['title' => 'v1']);

    RevisionStore::restore($old, $fixture, ['title']);

    $revisions = Revision::query()->where('subject_type', $fixture->getMorphClass())
        ->where('kind', 'manual')->orderBy('id')->get();

    expect($fixture->refresh()->title)->toBe('v1')
        ->and($revisions)->toHaveCount(2)
        ->and($revisions->last()->snapshot_json)->toBe(['title' => 'v2']);
});

test('prune keeps the newest manual revisions and leaves autosaves alone', function () {
    $fixture = RevisionFixture::create(['title' => 'x']);

    foreach (range(1, 5) as $i) {
        RevisionStore::record($fixture, 'manual', ['title' => "v{$i}"]);
    }
    RevisionStore::record($fixture, 'autosave', ['title' => 'draft']);

    $deleted = RevisionStore::prune($fixture, 'manual', 2);

    expect($deleted)->toBe(3)
        ->and(Revision::query()->where('kind', 'manual')->count())->toBe(2)
        ->and(Revision::query()->where('kind', 'autosave')->count())->toBe(1);
});
