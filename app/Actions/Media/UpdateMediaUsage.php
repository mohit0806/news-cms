<?php

declare(strict_types=1);

namespace App\Actions\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Media usage index writer (TRD §6.7; P2-T03.3).
 *
 * Rewrites the `media_usages` rows for one owner from a freshly extracted
 * media-id list. This powers "used in N items" and delete warnings without
 * scanning article bodies. Reads belong to the media capability (T05.4).
 */
final class UpdateMediaUsage
{
    /**
     * @param  list<int|string>  $mediaIds
     */
    public static function sync(Model $owner, array $mediaIds): void
    {
        $type = $owner->getMorphClass();
        $id = $owner->getKey();
        $now = now();

        $ids = array_values(array_unique(array_map(intval(...), $mediaIds)));

        DB::transaction(function () use ($type, $id, $ids, $now): void {
            DB::table('media_usages')->where('usable_type', $type)->where('usable_id', $id)->delete();

            foreach ($ids as $mediaId) {
                DB::table('media_usages')->insert([
                    'media_id' => $mediaId,
                    'usable_type' => $type,
                    'usable_id' => $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }
}
