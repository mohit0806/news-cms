<?php

declare(strict_types=1);

namespace App\Support\Media;

/**
 * Runtime image-codec detection (S9; P2-T05.3).
 *
 * AVIF conversions register only when the deployed Imagick actually encodes
 * AVIF; otherwise the pipeline stays WebP-only. Probed at runtime (not
 * install time) so workers and web agree even when they differ.
 */
final class ImageCodecs
{
    public static function supportsAvif(): bool
    {
        if (! extension_loaded('imagick')) {
            return false;
        }

        try {
            $formats = (new \Imagick)->queryFormats('AVIF');
        } catch (\Throwable) {
            return false;
        }

        return $formats !== [];
    }
}
