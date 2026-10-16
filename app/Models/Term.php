<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Category or tag term (PRD §6.2/§6.3, P2-T01.1).
 *
 * Translatable fields live on TermTranslation (unique per language/slug).
 * Categories nest up to 3 levels, enforced in code.
 *
 * @property int $id
 * @property string $vocabulary
 * @property int|null $parent_id
 * @property int $sort
 * @property string|null $color
 * @property bool $show_in_menu
 * @property bool $is_active
 */
#[Fillable(['vocabulary', 'parent_id', 'sort', 'color', 'show_in_menu', 'is_active'])]
class Term extends Model
{
    /** @use HasFactory<TermFactory> */
    use HasFactory, HasTranslations;

    /**
     * Maximum category nesting depth (PRD §6.2).
     */
    public const MAX_DEPTH = 3;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'show_in_menu' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'parent_id');
    }

    /**
     * @return HasMany<Term, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Term::class, 'parent_id')->orderBy('sort');
    }

    /**
     * @return HasMany<TermTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(TermTranslation::class);
    }

    /**
     * Depth of this term in the hierarchy (root = 1).
     */
    public function depth(): int
    {
        $depth = 1;
        $parent = $this->parent;

        while ($parent !== null) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }
}
