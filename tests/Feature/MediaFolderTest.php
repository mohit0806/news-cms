<?php

declare(strict_types=1);

use App\Models\MediaFolder;
use Illuminate\Support\Facades\DB;

test('folders nest and order children', function () {
    $root = MediaFolder::create(['name' => 'Events']);
    $child = MediaFolder::create(['name' => '2026', 'parent_id' => $root->getKey()]);

    expect($root->children)->toHaveCount(1)
        ->and($child->parent->is($root))->toBeTrue()
        ->and($root->wouldCycle($child->getKey()))->toBeTrue()
        ->and($root->wouldCycle(null))->toBeFalse()
        ->and($child->wouldCycle($root->getKey()))->toBeFalse();
});

test('a folder cannot parent to itself', function () {
    $folder = MediaFolder::create(['name' => 'Self']);

    expect($folder->wouldCycle($folder->getKey()))->toBeTrue();
});

test('link rows attach media ids with ordering', function () {
    $folder = MediaFolder::create(['name' => 'Press']);

    DB::table('media_folder_media')->insert([
        ['folder_id' => $folder->getKey(), 'media_id' => 9, 'sort' => 1],
        ['folder_id' => $folder->getKey(), 'media_id' => 3, 'sort' => 0],
    ]);

    expect(DB::table('media_folder_media')->where('folder_id', $folder->getKey())->count())->toBe(2);
});

test('deleting a folder removes links but nothing else', function () {
    $folder = MediaFolder::create(['name' => 'Temp']);

    DB::table('media_folder_media')->insert([
        'folder_id' => $folder->getKey(), 'media_id' => 11, 'sort' => 0,
    ]);

    $folder->delete();

    expect(DB::table('media_folder_media')->where('folder_id', $folder->getKey())->count())->toBe(0);
});
