<?php

declare(strict_types=1);

use App\Exceptions\ConflictException;
use App\Support\CompareAndSwap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VersionFixture extends Model
{
    protected $table = 'version_fixtures';

    protected $fillable = ['title', 'row_version'];

    public $timestamps = false;
}

beforeEach(function () {
    Schema::create('version_fixtures', function (Blueprint $table) {
        $table->id();
        $table->string('title')->nullable();
        $table->unsignedInteger('row_version')->default(1);
    });
});

afterEach(function () {
    Schema::dropIfExists('version_fixtures');
});

test('a non-conflicting save succeeds and bumps the version', function () {
    $fixture = VersionFixture::create(['title' => 'v1', 'row_version' => 1]);

    $saved = CompareAndSwap::attempt($fixture, 1, ['title' => 'v2']);

    expect($saved->title)->toBe('v2')
        ->and($saved->row_version)->toBe(2);
});

test('a stale save throws with a useful, safe message', function () {
    $fixture = VersionFixture::create(['title' => 'v1', 'row_version' => 1]);

    CompareAndSwap::attempt($fixture, 1, ['title' => 'v2']);

    $stale = VersionFixture::find($fixture->id);
    assert($stale instanceof VersionFixture);

    try {
        CompareAndSwap::attempt($stale, 1, ['title' => 'v3'], 'row_version', 'A. Editor');
        $this->fail('Expected a ConflictException.');
    } catch (ConflictException $e) {
        expect($e->getMessage())->toContain('Changed by A. Editor at ')
            ->and($e->getMessage())->toContain('expected 1');
    }

    // Loser change rolled back: winner state intact.
    expect(VersionFixture::find($fixture->id)->title)->toBe('v2');
});

test('concurrent writers cannot both succeed', function () {
    $fixture = VersionFixture::create(['title' => 'v1', 'row_version' => 1]);

    $readerA = VersionFixture::find($fixture->id);
    $readerB = VersionFixture::find($fixture->id);
    assert($readerA instanceof VersionFixture);
    assert($readerB instanceof VersionFixture);

    CompareAndSwap::attempt($readerA, 1, ['title' => 'winner']);

    expect(fn () => CompareAndSwap::attempt($readerB, 1, ['title' => 'loser']))
        ->toThrow(ConflictException::class);
});
