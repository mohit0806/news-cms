<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Static page (PRD §6.5/§20, P2-T08.1).
 *
 * Slugs are globally unique across pages, category slugs, and reserved words
 * (enforced in PagesController, consumed by P3's resolver).
 *
 * @property int $id
 * @property string $language
 * @property string|null $translation_group_id
 * @property string $title
 * @property string $slug
 * @property array<string, mixed>|null $body_json
 * @property string|null $body_html
 * @property string $template
 * @property string $status
 */
#[Fillable([
    'language', 'translation_group_id', 'title', 'slug', 'body_json',
    'body_html', 'template', 'status', 'meta_title', 'meta_description',
    'allow_indexing', 'created_by', 'updated_by',
])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'body_json' => 'array',
            'allow_indexing' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * @param  Builder<Page>  $query
     * @return Builder<Page>
     */
    public function scopeInLanguage(Builder $query, string $lang): Builder
    {
        return $query->where('language', $lang);
    }
}
