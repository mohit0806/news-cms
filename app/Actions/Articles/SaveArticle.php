<?php

declare(strict_types=1);

namespace App\Actions\Articles;

use App\Actions\Media\UpdateMediaUsage;
use App\Exceptions\ConflictException;
use App\Models\Article;
use App\Models\User;
use App\Support\CompareAndSwap;
use App\Support\Editor\BlockSchema;
use App\Support\Editor\BodyRenderer;
use App\Support\RevisionStore;
use App\Support\Slugger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Create/update an article draft (PRD §6.1; TRD §6.4; P2-T03.3).
 *
 * Owns validation, slug generation, body rendering, revision recording,
 * compare-and-swap concurrency, taxonomy/author/related syncing, and media
 * usage sync. Status transitions live in T03.4; this action never changes
 * status. Callers authorize create/update before invoking.
 */
final class SaveArticle
{
    /**
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     * @throws ConflictException
     */
    public static function run(User $actor, ?Article $article, array $input): Article
    {
        $data = self::validated($input, $article);

        return DB::transaction(function () use ($actor, $article, $data): Article {
            if ($article === null) {
                $created = Article::create([
                    ...$data['columns'],
                    'status' => 'draft',
                    'row_version' => 1,
                    'created_by' => $actor->getKey(),
                    'updated_by' => $actor->getKey(),
                ]);

                RevisionStore::record($created, 'manual', self::snapshot($created), (int) $actor->getKey());
                self::syncRelations($created, $data);
                UpdateMediaUsage::sync($created, $data['mediaIds']);

                return $created;
            }

            $changedBy = $article->updated_by === null
                ? null
                : (string) User::query()->whereKey($article->updated_by)->value('name');

            $updated = CompareAndSwap::attempt(
                $article,
                (int) $data['expected_row_version'],
                [...$data['columns'], 'updated_by' => $actor->getKey()],
                'row_version',
                $changedBy,
            );

            RevisionStore::record($updated, 'manual', self::snapshot($updated), (int) $actor->getKey());
            RevisionStore::prune($updated, 'manual', 50);
            self::syncRelations($updated, $data);
            UpdateMediaUsage::sync($updated, $data['mediaIds']);

            return $updated;
        });
    }

    /**
     * Record an autosave revision without touching the article row (PRD §6.1.4).
     *
     * @param  array<string, mixed>  $input
     */
    public static function autosave(User $actor, Article $article, array $input): void
    {
        $data = self::validated($input, $article, true);

        RevisionStore::record($article, 'autosave', [
            'title' => $data['columns']['title'],
            'subtitle' => $data['columns']['subtitle'],
            'summary' => $data['columns']['summary'],
            'body_json' => $data['columns']['body_json'],
        ], (int) $actor->getKey());

        RevisionStore::prune($article, 'autosave', 1);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{columns: array<string, mixed>, mediaIds: list<int|string>, expected_row_version: int|null, extra_categories: list<int>, tags: list<int>, co_authors: list<int>, related: list<int>}
     */
    private static function validated(array $input, ?Article $article, bool $autosave = false): array
    {
        $languages = config('app.site_languages', ['en']);
        $languages = is_array($languages) && $languages !== [] ? array_values($languages) : ['en'];

        $validator = Validator::make($input, [
            'language' => ['required', 'string', Rule::in($languages)],
            'title' => ['required', 'string', 'max:200'],
            'subtitle' => ['nullable', 'string', 'max:250'],
            'slug' => ['nullable', 'string', 'max:190'],
            'summary' => ['nullable', 'string', 'max:400'],
            'body' => ['required'],
            'primary_category_id' => ['required', 'integer', Rule::exists('terms', 'id')->where('vocabulary', 'category')],
            'extra_categories' => ['nullable', 'array', 'max:3'],
            'extra_categories.*' => ['integer', Rule::exists('terms', 'id')->where('vocabulary', 'category')],
            'tags' => ['nullable', 'array', 'max:15'],
            'tags.*' => ['integer', Rule::exists('terms', 'id')->where('vocabulary', 'tag')],
            'author_id' => ['required', 'integer', Rule::exists('authors', 'id')],
            'co_authors' => ['nullable', 'array', 'max:5'],
            'co_authors.*' => ['integer', Rule::exists('authors', 'id')],
            'related' => ['nullable', 'array', 'max:6'],
            'related.*' => ['integer', Rule::exists('articles', 'id')],
            'dateline' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:100'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:170'],
            'canonical_url' => ['nullable', 'string', 'max:2000'],
            'is_breaking' => ['nullable', 'boolean'],
            'breaking_until' => ['nullable', 'date'],
            'expected_row_version' => $article === null || $autosave ? ['nullable', 'integer'] : ['required', 'integer'],
        ]);

        /** @var array<string, mixed> $valid */
        $valid = $validator->validate();

        $language = (string) $valid['language'];
        $slug = trim((string) ($valid['slug'] ?? ''));

        if ($slug === '') {
            $slug = Slugger::slug((string) $valid['title']);
        }

        $taken = fn (string $candidate): bool => Article::query()
            ->where('language', $language)
            ->where('slug', $candidate)
            ->when($article !== null, fn ($query) => $query->whereKeyNot($article->getKey()))
            ->exists();

        if ($taken($slug)) {
            $slug = Slugger::unique($slug, $taken);
        }

        $body = $valid['body'];

        if (is_string($body)) {
            try {
                $body = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw ValidationException::withMessages(['body' => __('The body is not valid JSON.')]);
            }
        }

        try {
            BlockSchema::validate($body);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['body' => $exception->getMessage()]);
        }

        $rendered = (new BodyRenderer)->render($body);

        $extra = array_values(array_map(intval(...), (array) ($valid['extra_categories'] ?? [])));

        if (in_array((int) $valid['primary_category_id'], $extra, true)) {
            throw ValidationException::withMessages([
                'extra_categories' => __('The primary category must not repeat as an extra category.'),
            ]);
        }

        $related = array_values(array_map(intval(...), (array) ($valid['related'] ?? [])));

        if ($article !== null && in_array((int) $article->getKey(), $related, true)) {
            throw ValidationException::withMessages(['related' => __('An article cannot relate to itself.')]);
        }

        $authorId = (int) $valid['author_id'];
        $coAuthors = array_values(array_map(intval(...), (array) ($valid['co_authors'] ?? [])));

        if (in_array($authorId, $coAuthors, true)) {
            throw ValidationException::withMessages([
                'co_authors' => __('The primary author must not repeat as a co-author.'),
            ]);
        }

        return [
            'columns' => [
                'type' => 'standard',
                'language' => $language,
                'title' => $valid['title'],
                'subtitle' => $valid['subtitle'] ?? null,
                'slug' => $slug,
                'summary' => $valid['summary'] ?? null,
                'body_json' => $body,
                'body_html' => $rendered->html,
                'body_text' => $rendered->text,
                'primary_category_id' => (int) $valid['primary_category_id'],
                'author_id' => $authorId,
                'dateline' => $valid['dateline'] ?? null,
                'source' => $valid['source'] ?? null,
                'meta_title' => $valid['meta_title'] ?? null,
                'meta_description' => $valid['meta_description'] ?? null,
                'canonical_url' => $valid['canonical_url'] ?? null,
                'is_breaking' => (bool) ($valid['is_breaking'] ?? false),
                'breaking_until' => $valid['breaking_until'] ?? null,
                'reading_time_min' => $rendered->readingTimeMin,
            ],
            'mediaIds' => $rendered->mediaIds,
            'expected_row_version' => isset($valid['expected_row_version']) ? (int) $valid['expected_row_version'] : null,
            'extra_categories' => $extra,
            'tags' => array_values(array_map(intval(...), (array) ($valid['tags'] ?? []))),
            'co_authors' => $coAuthors,
            'related' => $related,
        ];
    }

    /**
     * @param  array{extra_categories: list<int>, tags: list<int>, co_authors: list<int>, related: list<int>}  $data
     */
    private static function syncRelations(Article $article, array $data): void
    {
        DB::table('termables')
            ->where('termable_type', $article->getMorphClass())
            ->where('termable_id', $article->getKey())
            ->delete();

        $sort = 0;

        foreach ([...$data['extra_categories'], ...$data['tags']] as $termId) {
            DB::table('termables')->insert([
                'term_id' => $termId,
                'termable_type' => $article->getMorphClass(),
                'termable_id' => $article->getKey(),
                'sort' => $sort++,
            ]);
        }

        $article->coAuthors()->sync(
            collect($data['co_authors'])->mapWithKeys(fn (int $id, int $index): array => [$id => ['sort' => $index]])->all()
        );

        $article->relatedArticles()->sync(
            collect($data['related'])->mapWithKeys(fn (int $id, int $index): array => [$id => ['sort' => $index]])->all()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function snapshot(Article $article): array
    {
        return $article->only(['title', 'subtitle', 'summary', 'body_json']);
    }
}
