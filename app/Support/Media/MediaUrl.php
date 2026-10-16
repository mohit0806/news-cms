<?php

declare(strict_types=1);

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Media URL generation (TRD §10.3; P2-T05.3).
 *
 * The only sanctioned path from a media row to a public URL: applies the
 * optional MEDIA_CDN_URL base to the package URL. Originals and conversions
 * take immutable cache headers at serve time because paths are content-unique.
 */
final class MediaUrl
{
    public static function to(Media $media, string $conversion = ''): string
    {
        $url = $conversion === '' ? $media->getFullUrl() : $media->getFullUrl($conversion);
        $cdn = (string) config('media.cdn_url', '');

        if ($cdn === '') {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return rtrim($cdn, '/').'/'.ltrim((string) $path, '/');
    }
}
