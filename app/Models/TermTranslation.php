<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-language term fields (PRD §6.2/§6.3, P2-T01.1).
 *
 * @property int $id
 * @property int $term_id
 * @property string $language
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $meta_title
 * @property string|null $meta_description
 */
#[Fillable(['term_id', 'language', 'name', 'slug', 'description', 'meta_title', 'meta_description'])]
class TermTranslation extends Model
{
    /**
     * @return BelongsTo<Term, $this>
     */
    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }
}
