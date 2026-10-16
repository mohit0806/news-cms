<?php

declare(strict_types=1);

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

/**
 * Randomized storage paths (PRD §7.2; P2-T05.3).
 *
 * Originals live at `{yyyy}/{mm}/{id}-{hash}/…`; conversions and responsive
 * images nest below. The segment derives deterministically from the media id
 * so all three path methods agree. Paths are content-unique, so immutable
 * cache headers stay safe (TRD §10.3).
 */
final class UuidPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        $date = $media->created_at ?? now();
        $hash = substr(md5((string) $media->getKey().config('app.key')), 0, 8);

        return $date->format('Y/m/').$media->getKey().'-'.$hash.'/';
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media).'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media).'responsive/';
    }
}
