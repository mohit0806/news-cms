<?php

declare(strict_types=1);

namespace App\Actions\Articles;

use App\Events\ArticlePublished;
use App\Events\ArticleRejected;
use App\Events\ArticleSubmitted;
use App\Events\ArticleUnpublished;
use App\Models\Article;
use App\Models\User;
use App\Settings\ContentSettings;
use App\Support\RevisionStore;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Editorial status transitions (PRD §6.1.3; P2-T03.4).
 *
 * Owns transition guards, publish validation, scheduling semantics, manual
 * revisions, and post-commit event fan-out (audit + notifications). Status
 * changes never bump `row_version` — that column guards edit conflicts only.
 * A null actor means the scheduler (system transitions skip permission gates
 * but keep every invariant).
 */
final class TransitionArticle
{
    /**
     * @param  array<string, mixed>  $options
     *
     * @throws ValidationException
     */
    public static function run(?User $actor, Article $article, string $to, array $options = []): Article
    {
        $from = $article->status;

        $transition = match (true) {
            $to === 'in_review' && $from === 'draft' => 'submit',
            $to === 'draft' && $from === 'in_review' => 'reject',
            $to === 'published' && in_array($from, ['draft', 'in_review', 'unpublished', 'scheduled'], true) => 'publish',
            $to === 'draft' && $from === 'scheduled' => 'unschedule',
            $to === 'unpublished' && $from === 'published' => 'unpublish',
            default => throw ValidationException::withMessages([
                'status' => __("Cannot move an article from {$from} to {$to}."),
            ]),
        };

        if ($actor !== null) {
            self::authorize($actor, $article, $transition, $options);
        } elseif ($transition !== 'publish') {
            throw ValidationException::withMessages(['status' => __('Only publishing runs unattended.')]);
        }

        if ($transition === 'publish') {
            self::validatePublishable($article, $options);
        }

        if ($transition === 'reject' && trim((string) ($options['note'] ?? '')) === '') {
            throw ValidationException::withMessages(['note' => __('A rejection note is required.')]);
        }

        $attributes = match ($transition) {
            'submit' => ['status' => 'in_review', 'review_note' => null],
            'reject' => ['status' => 'draft', 'review_note' => trim((string) ($options['note'] ?? ''))],
            'publish' => self::publishAttributes($article, $options),
            'unschedule' => ['status' => 'draft', 'published_at' => null],
            'unpublish' => ['status' => 'unpublished'],
        };

        $updated = DB::transaction(function () use ($article, $attributes): Article {
            $article->forceFill($attributes)->save();
            $article->refresh();

            RevisionStore::record($article, 'manual', $article->only([
                'title', 'subtitle', 'summary', 'body_json', 'status',
            ]), $article->updated_by);

            return $article;
        });

        match ($transition) {
            'submit' => event(new ArticleSubmitted($updated, self::actorOrFail($actor))),
            'reject' => event(new ArticleRejected($updated, self::actorOrFail($actor), (string) $updated->review_note)),
            'publish' => event(new ArticlePublished($updated, $actor)),
            'unschedule', 'unpublish' => event(new ArticleUnpublished($updated, self::actorOrFail($actor))),
        };

        return $updated;
    }

    /**
     * Non-publish transitions always carry a staff actor (the scheduler only
     * ever publishes); the guard above enforces it, this names the type.
     */
    private static function actorOrFail(?User $actor): User
    {
        if ($actor === null) {
            throw ValidationException::withMessages(['status' => __('Only publishing runs unattended.')]);
        }

        return $actor;
    }

    /**
     * Flip due scheduled articles (used by `articles:publish-scheduled`, T03.5).
     *
     * @return list<int> published article ids
     */
    public static function publishDue(int $limit = 100): array
    {
        $ids = Article::query()
            ->where('status', 'scheduled')
            ->where('published_at', '<=', now())
            ->orderBy('published_at')
            ->limit($limit)
            ->pluck('id')
            ->all();

        $published = [];

        foreach ($ids as $id) {
            $article = Article::query()->whereKey($id)->first();

            if (! $article instanceof Article || $article->status !== 'scheduled') {
                continue;
            }

            try {
                self::run(null, $article, 'published');
                $published[] = (int) $article->getKey();
            } catch (ValidationException) {
                continue;
            }
        }

        return $published;
    }

    /**
     * @param  'submit'|'reject'|'publish'|'unschedule'|'unpublish'  $transition
     * @param  array<string, mixed>  $options
     */
    private static function authorize(User $actor, Article $article, string $transition, array $options): void
    {
        $allowed = match ($transition) {
            'submit' => $actor->can('submit', $article),
            'reject' => $actor->can('articles.publish'),
            'publish' => $actor->can('articles.publish'),
            'unschedule' => $actor->can('articles.unpublish'),
            'unpublish' => $actor->can('articles.unpublish'),
        };

        if (! $allowed) {
            throw ValidationException::withMessages(['status' => __('You may not perform this transition.')]);
        }

        if ($transition === 'publish' && ($options['is_breaking'] ?? $article->is_breaking) && ! $actor->can('articles.set_breaking')) {
            throw ValidationException::withMessages(['is_breaking' => __('Marking breaking news is not permitted.')]);
        }
    }

    /**
     * Hard publish block with clear messages (PRD §6.1.2).
     *
     * @param  array<string, mixed>  $options
     */
    private static function validatePublishable(Article $article, array $options): void
    {
        if (trim($article->title) === '' || trim($article->slug) === '') {
            throw ValidationException::withMessages(['title' => __('A title and slug are required to publish.')]);
        }

        if (empty($article->body_text) || empty($article->body_json)) {
            throw ValidationException::withMessages(['body' => __('Body content is required to publish.')]);
        }

        if ($article->primary_category_id === null || $article->author_id === null) {
            throw ValidationException::withMessages(['article' => __('A category and author are required to publish.')]);
        }

        if (($options['published_at'] ?? $article->published_at) === null) {
            throw ValidationException::withMessages(['published_at' => __('A publication date is required to publish.')]);
        }

        if (app(ContentSettings::class)->require_featured_image && ! $article->hasMedia('featured')) {
            throw ValidationException::withMessages(['featured' => __('A featured image is required to publish.')]);
        }

        if (app(ContentSettings::class)->require_alt_text && $article->hasMedia('featured')) {
            $alt = (string) $article->getFirstMedia('featured')?->getCustomProperty('alt', '');

            if (trim($alt) === '') {
                throw ValidationException::withMessages(['featured' => __('The featured image needs alt text.')]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    private static function publishAttributes(Article $article, array $options): array
    {
        $raw = $options['published_at'] ?? $article->published_at;
        $publishedAt = $raw instanceof CarbonInterface ? $raw : ($raw !== null ? Carbon::parse($raw) : null);

        $attributes = [
            'status' => $publishedAt !== null && $publishedAt->isFuture() ? 'scheduled' : 'published',
            'published_at' => $publishedAt,
        ];

        if (array_key_exists('is_breaking', $options)) {
            $attributes['is_breaking'] = (bool) $options['is_breaking'];
            $attributes['breaking_until'] = $options['breaking_until']
                ?? now()->addHours((int) config('app.breaking_default_hours', 6));
        }

        return $attributes;
    }
}
