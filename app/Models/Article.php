<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * News article (PRD §6.1/§20, P2-T03.1).
 *
 * `body_text` is stored alongside `body_html` per TRD §8.3 (search source for
 * PRD §14) although PRD §20 omits the column. Articles are never hard-deleted
 * while P2 code owns them; trash purges via `trash:purge` (T03.5).
 *
 * @property int $id
 * @property string $type
 * @property string $language
 * @property string|null $translation_group_id
 * @property string $title
 * @property string|null $subtitle
 * @property string $slug
 * @property string|null $summary
 * @property array<string, mixed>|null $body_json
 * @property string|null $body_html
 * @property string|null $body_text
 * @property int|null $primary_category_id
 * @property int|null $author_id
 * @property string $status
 * @property Carbon|null $published_at
 * @property bool $is_breaking
 * @property Carbon|null $breaking_until
 * @property bool $allow_indexing
 * @property string|null $review_note
 * @property int $row_version
 * @property int|null $created_by
 * @property int|null $updated_by
 */
#[Fillable([
    'type', 'language', 'translation_group_id', 'title', 'subtitle', 'slug',
    'summary', 'body_json', 'body_html', 'body_text', 'featured_caption',
    'featured_credit', 'primary_category_id', 'author_id', 'dateline', 'source',
    'status', 'published_at', 'is_breaking', 'breaking_until', 'live_status',
    'allow_indexing', 'meta_title', 'meta_description', 'canonical_url',
    'reading_time_min', 'total_views', 'review_note', 'row_version',
    'created_by', 'updated_by',
])]
class Article extends Model implements HasMedia
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'body_json' => 'array',
            'published_at' => 'datetime',
            'is_breaking' => 'boolean',
            'breaking_until' => 'datetime',
            'allow_indexing' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Term, $this>
     */
    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'primary_category_id');
    }

    /**
     * @return BelongsTo<Author, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    /**
     * Attachment collections (PRD §6.1.2; P2-T03.4).
     *
     * Display conversions for these collections land with the picker
     * integration (T05.4); until then `x-picture` serves originals.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured');
        $this->addMediaCollection('og');
    }

    /**
     * @return BelongsToMany<Author, $this>
     */
    public function coAuthors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'article_authors')->withPivot('sort')->orderByPivot('sort');
    }

    /**
     * @return BelongsToMany<Article, $this>
     */
    public function relatedArticles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_related', 'article_id', 'related_article_id')
            ->withPivot('sort')->orderByPivot('sort');
    }

    /**
     * Publicly visible content only (TRD §6.2).
     *
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->where('published_at', '<=', now());
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopeInLanguage(Builder $query, string $lang): Builder
    {
        return $query->where('language', $lang);
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopeInCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('primary_category_id', $categoryId);
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id');
    }

    /**
     * List columns excluding heavy body payloads (TRD §6.2).
     *
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopeWithListColumns(Builder $query): Builder
    {
        return $query->select([
            'id', 'type', 'language', 'title', 'slug', 'summary', 'primary_category_id',
            'author_id', 'status', 'published_at', 'is_breaking', 'allow_indexing',
            'reading_time_min', 'total_views', 'created_at', 'updated_at',
        ]);
    }
}
