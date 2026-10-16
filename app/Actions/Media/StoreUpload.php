<?php

declare(strict_types=1);

namespace App\Actions\Media;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Validated media upload into the library (PRD §7; TRD §10.1; P2-T05.3).
 *
 * Validation runs before storing: size caps by type, extension allowlist
 * (blocked types always rejected), real MIME from file contents, and an
 * image-dimension bomb guard. Uploads attach to the uploading staff member
 * (uploader filter, PRD §7.1); folder assignment lives in NewsCMS-owned
 * `media_folder_media` (ADR-0001). SHA-256 dedupe reuses the existing record.
 */
final class StoreUpload
{
    /**
     * Expected MIME per extension (finfo result must match).
     *
     * @var array<string, list<string>>
     */
    private const MIME_MAP = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
        'gif' => ['image/gif'],
        'avif' => ['image/avif'],
        'pdf' => ['application/pdf'],
        'mp3' => ['audio/mpeg'],
        'm4a' => ['audio/mp4', 'audio/x-m4a'],
        'mp4' => ['video/mp4'],
        'webm' => ['video/webm'],
    ];

    /**
     * @param  array{title?: string, alt?: string, focal_x?: int, focal_y?: int}  $meta
     *
     * @throws ValidationException
     */
    public static function store(
        User $uploader,
        UploadedFile $file,
        ?int $folderId = null,
        array $meta = [],
    ): Media {
        $type = self::classify($file);
        self::checkSize($file, $type);

        $mime = (string) mime_content_type($file->getPathname());
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($mime, self::MIME_MAP[$extension] ?? [], true)) {
            throw ValidationException::withMessages(['file' => __('This file’s contents do not match its extension.')]);
        }

        $dimensions = self::imageDimensions($file, $mime);

        $hash = hash_file('sha256', $file->getPathname());

        if ($hash === false) {
            throw ValidationException::withMessages(['file' => __('The upload could not be read.')]);
        }

        $existing = Media::query()->where('custom_properties->sha256', $hash)->first();

        if ($existing instanceof Media) {
            return $existing;
        }

        $storedName = (string) Str::uuid().'.'.$extension;

        $media = $uploader
            ->addMedia($file->getPathname())
            ->usingName($file->getClientOriginalName())
            ->usingFileName($storedName)
            ->withCustomProperties([
                'sha256' => $hash,
                'width' => $dimensions[0],
                'height' => $dimensions[1],
                'duration' => self::duration($file, $mime),
                'title' => $meta['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'alt' => $meta['alt'] ?? '',
                'focal_x' => max(0, min(100, (int) ($meta['focal_x'] ?? 50))),
                'focal_y' => max(0, min(100, (int) ($meta['focal_y'] ?? 50))),
            ])
            ->toMediaCollection("library-{$type}");

        if ($folderId !== null) {
            DB::table('media_folder_media')->updateOrInsert(
                ['folder_id' => $folderId, 'media_id' => $media->getKey()],
                ['sort' => 0, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        return $media;
    }

    /**
     * @return string image|document|audio|video
     */
    private static function classify(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $allowed = config('media.allowed_extensions', []);

        foreach (is_array($allowed) ? $allowed : [] as $type => $extensions) {
            if (in_array($extension, (array) $extensions, true)) {
                return (string) $type;
            }
        }

        throw ValidationException::withMessages(['file' => __('This file type is not allowed.')]);
    }

    private static function checkSize(UploadedFile $file, string $type): void
    {
        $caps = config('media.max_upload_mb', []);
        $capMb = (int) (is_array($caps) ? ($caps[$type] ?? 0) : 0);

        if ($capMb <= 0 || $file->getSize() > $capMb * 1024 * 1024) {
            throw ValidationException::withMessages([
                'file' => __('This file exceeds the :cap MB limit.', ['cap' => $capMb]),
            ]);
        }
    }

    /**
     * Pixel dimensions for images; [0, 0] otherwise. Rejects decompression
     * bombs without decoding (TRD §10.1).
     *
     * @return array{int, int}
     */
    private static function imageDimensions(UploadedFile $file, string $mime): array
    {
        if (! str_starts_with($mime, 'image/')) {
            return [0, 0];
        }

        try {
            $ping = new \Imagick;
            $ping->pingImage($file->getPathname());
            $width = (int) $ping->getImageWidth();
            $height = (int) $ping->getImageHeight();
        } catch (\Throwable) {
            throw ValidationException::withMessages(['file' => __('This image could not be read.')]);
        }

        if ($width > 12000 || $height > 12000 || $width * $height > 100 * 1000 * 1000) {
            throw ValidationException::withMessages(['file' => __('This image is too large to process safely.')]);
        }

        return [$width, $height];
    }

    /**
     * Audio/video duration in seconds via ffprobe when present, else null.
     */
    private static function duration(UploadedFile $file, string $mime): ?float
    {
        if (! str_starts_with($mime, 'audio/') && ! str_starts_with($mime, 'video/')) {
            return null;
        }

        $ffprobe = trim((string) shell_exec('command -v ffprobe'));

        if ($ffprobe === '') {
            return null;
        }

        $output = shell_exec(
            escapeshellcmd($ffprobe).' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '
            .escapeshellarg($file->getPathname())
        );

        return is_numeric(trim((string) $output)) ? (float) trim((string) $output) : null;
    }
}
