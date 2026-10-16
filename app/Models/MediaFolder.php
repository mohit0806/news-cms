<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Nested media folder (ADR-0001, P2-T05.2).
 *
 * NewsCMS owns folders and the folder↔media link; the package owns media
 * records. Deleting a folder removes its link rows, never media rows.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 */
#[Fillable(['parent_id', 'name'])]
class MediaFolder extends Model
{
    /**
     * @return BelongsTo<MediaFolder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'parent_id');
    }

    /**
     * @return HasMany<MediaFolder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(MediaFolder::class, 'parent_id')->orderBy('name');
    }

    /**
     * Package media rows linked into this folder (read-only side).
     *
     * @return BelongsToMany<Media, $this>
     */
    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'media_folder_media', 'folder_id', 'media_id')
            ->withPivot('sort')
            ->orderByPivot('sort');
    }

    /**
     * Whether assigning $parentId as parent would create a cycle.
     */
    public function wouldCycle(?int $parentId): bool
    {
        if ($parentId === null || $this->getKey() === null) {
            return false;
        }

        if ((int) $parentId === (int) $this->getKey()) {
            return true;
        }

        $ancestor = MediaFolder::query()->whereKey($parentId)->first();

        while ($ancestor !== null) {
            if ((int) $ancestor->getKey() === (int) $this->getKey()) {
                return true;
            }

            $ancestor = $ancestor->parent;
        }

        return false;
    }
}
