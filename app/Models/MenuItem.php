<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Menu entry (PRD §6.6, P2-T08.2).
 *
 * Linked targets that are deleted or unpublished are hidden on the public
 * site and flagged in admin via `isBroken()`; P3 renders the visible subset.
 *
 * @property int $id
 * @property int $menu_id
 * @property int|null $parent_id
 * @property string $label
 * @property string $type
 * @property int|null $target_id
 * @property string|null $url
 * @property bool $open_new_tab
 * @property int $sort
 * @property bool $is_active
 */
#[Fillable([
    'menu_id', 'parent_id', 'label', 'type', 'target_id', 'url',
    'open_new_tab', 'sort', 'is_active',
])]
class MenuItem extends Model
{
    /**
     * Maximum item nesting depth (PRD §6.6: max 2 levels).
     */
    public const MAX_DEPTH = 2;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'open_new_tab' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Menu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * @return BelongsTo<MenuItem, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort');
    }

    /**
     * Whether the linked target is gone or unpublished (admin flag).
     */
    public function isBroken(): bool
    {
        if ($this->type === 'url') {
            return $this->url === null || $this->url === '';
        }

        if ($this->target_id === null) {
            return true;
        }

        return match ($this->type) {
            'category' => ! Term::query()
                ->whereKey($this->target_id)
                ->where('vocabulary', 'category')
                ->where('is_active', true)
                ->exists(),
            'tag' => ! Term::query()
                ->whereKey($this->target_id)
                ->where('vocabulary', 'tag')
                ->exists(),
            'page' => ! Page::query()
                ->whereKey($this->target_id)
                ->where('status', 'published')
                ->exists(),
            'article' => ! Article::query()
                ->whereKey($this->target_id)
                ->where('status', 'published')
                ->exists(),
            default => true,
        };
    }
}
