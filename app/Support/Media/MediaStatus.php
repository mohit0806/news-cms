<?php

declare(strict_types=1);

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Derived processing state (P2-T05.3).
 *
 * The package tracks generated conversions per row; there is no status
 * column. Non-image uploads are ready once stored. The picker reads this
 * for its "processing" badge; failures surface via failed_jobs (system page).
 */
final class MediaStatus
{
    public static function of(Media $media): string
    {
        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            return 'ready';
        }

        return $media->getGeneratedConversions()->isNotEmpty() ? 'ready' : 'processing';
    }
}
