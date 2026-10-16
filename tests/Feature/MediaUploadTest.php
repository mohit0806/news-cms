<?php

declare(strict_types=1);

use App\Actions\Media\StoreUpload;
use App\Models\MediaFolder;
use App\Models\User;
use App\Support\Media\ImageCodecs;
use App\Support\Media\MediaStatus;
use App\Support\Media\MediaUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Storage::fake('public');
    config(['media-library.disk_name' => 'public']);
});

function uploader(): User
{
    return User::factory()->create();
}

function tinyJpeg(string $name = 'photo.jpg', int $width = 40, int $height = 30): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'upl').'.jpg';

    $image = new Imagick;
    $image->newImage($width, $height, new ImagickPixel('red'));
    $image->setImageFormat('jpeg');
    $image->writeImage($path);
    $image->clear();

    return new UploadedFile($path, $name, 'image/jpeg', null, true);
}

test('valid images store with metadata and conversions', function () {
    $media = StoreUpload::store(uploader(), tinyJpeg())->refresh();

    expect($media->collection_name)->toBe('library-image')
        ->and($media->getCustomProperty('sha256'))->toMatch('/^[0-9a-f]{64}$/')
        ->and($media->getCustomProperty('width'))->toBe(40)
        ->and($media->getCustomProperty('height'))->toBe(30)
        ->and($media->file_name)->toEndWith('.jpg')
        ->and($media->hasGeneratedConversion('w-320'))->toBeTrue()
        ->and(MediaStatus::of($media))->toBe('ready');

    // getPath() is absolute on local disks; the fake disk is local too.
    expect(file_exists($media->getPath()))->toBeTrue();
});

test('re-uploads dedupe to the existing record', function () {
    $user = uploader();
    $first = StoreUpload::store($user, tinyJpeg());
    $second = StoreUpload::store($user, tinyJpeg());

    expect(Media::count())->toBe(1)
        ->and($second->getKey())->toBe($first->getKey());
});

test('disguised executables are rejected', function () {
    $path = tempnam(sys_get_temp_dir(), 'upl').'.jpg';
    file_put_contents($path, '<?php echo "pwned";');

    $spoof = new UploadedFile($path, 'evil.jpg', 'image/jpeg', null, true);

    expect(fn () => StoreUpload::store(uploader(), $spoof))
        ->toThrow(ValidationException::class);

    $svg = new UploadedFile($path, 'evil.svg', 'image/svg+xml', null, true);

    expect(fn () => StoreUpload::store(uploader(), $svg))
        ->toThrow(ValidationException::class);

    $php = new UploadedFile($path, 'evil.php', 'application/x-php', null, true);

    expect(fn () => StoreUpload::store(uploader(), $php))
        ->toThrow(ValidationException::class);

    expect(Media::count())->toBe(0);
});

test('oversized files are rejected before storing', function () {
    $path = tempnam(sys_get_temp_dir(), 'upl').'.jpg';
    $image = new Imagick;
    $image->newImage(10, 10, new ImagickPixel('blue'));
    $image->setImageFormat('jpeg');
    $image->writeImage($path);
    $image->clear();

    // Pad past the 20 MB image cap; magic bytes stay a valid JPEG.
    $handle = fopen($path, 'r+b');
    fseek($handle, 21 * 1024 * 1024);
    fwrite($handle, "\0");
    fclose($handle);

    clearstatcache(true, $path);

    $big = new UploadedFile($path, 'big.jpg', 'image/jpeg', null, true);

    expect(fn () => StoreUpload::store(uploader(), $big))
        ->toThrow(ValidationException::class);

    expect(Media::count())->toBe(0);
});

test('decompression bombs are rejected without decoding', function () {
    $wide = tinyJpeg('wide.jpg', 12001, 10);

    expect(fn () => StoreUpload::store(uploader(), $wide))
        ->toThrow(ValidationException::class);

    expect(Media::count())->toBe(0);
});

test('uploads link into folders on request', function () {
    $folder = MediaFolder::create(['name' => 'Press']);

    $media = StoreUpload::store(uploader(), tinyJpeg(), $folder->getKey());

    expect(DB::table('media_folder_media')
        ->where('folder_id', $folder->getKey())
        ->where('media_id', $media->getKey())
        ->exists())->toBeTrue();
});

test('media urls apply the cdn base when configured', function () {
    $media = StoreUpload::store(uploader(), tinyJpeg());

    config(['media.cdn_url' => 'https://cdn.example.test']);

    expect(MediaUrl::to($media))->toStartWith('https://cdn.example.test/');

    config(['media.cdn_url' => '']);

    expect(MediaUrl::to($media))->toStartWith('http')
        ->and(MediaUrl::to($media))->toContain($media->file_name);
});

test('codec detection reports a boolean', function () {
    expect(ImageCodecs::supportsAvif())->toBeBool();
});

test('picture component renders srcset from conversions', function () {
    $media = StoreUpload::store(uploader(), tinyJpeg());

    $html = (string) $this->blade(
        '<x-picture :media="$media" crop="card" alt="A photo" />',
        ['media' => $media->refresh()]
    );

    expect($html)->toContain('<picture>')
        ->toContain('type="image/avif"')
        ->toContain('type="image/webp"')
        ->toContain('320w')
        ->toContain('loading="lazy"')
        ->toContain('alt="A photo"');

    $eager = (string) $this->blade(
        '<x-picture :media="$media" :eager="true" />',
        ['media' => $media]
    );

    expect($eager)->toContain('fetchpriority="high"')
        ->not->toContain('loading="lazy"');
});

test('picture falls back to the pending hook without media', function () {
    $html = (string) $this->blade('<x-picture :media-id="7" alt="Pending" />');

    expect($html)->toContain('data-media-id="7"')
        ->toContain('cms-picture-pending');
});
