<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ArticlePublished;
use App\Events\ArticleRejected;
use App\Events\ArticleSubmitted;
use App\Events\ArticleUnpublished;
use App\Support\Audit;
use Illuminate\Contracts\Events\ShouldBeDiscovered;

/**
 * Audit trail for editorial transitions (PRD §16; P2-T03.4).
 *
 * Registered explicitly in ArticleServiceProvider and opted out of
 * auto-discovery: the framework-default base EventServiceProvider discovers
 * app/Listeners, which would otherwise register every handler twice.
 */
class RecordArticleAudit implements ShouldBeDiscovered
{
    public static function shouldBeDiscovered(): bool
    {
        return false;
    }

    public function handle(
        ArticleSubmitted|ArticleRejected|ArticlePublished|ArticleUnpublished $event
    ): void {
        [$action, $actor, $properties] = match (true) {
            $event instanceof ArticleSubmitted => [
                'articles.submitted', $event->actor, ['to' => 'in_review'],
            ],
            $event instanceof ArticleRejected => [
                'articles.rejected', $event->actor, ['to' => 'draft', 'note' => $event->note],
            ],
            $event instanceof ArticlePublished => [
                'articles.published', $event->actor, ['to' => $event->article->status],
            ],
            $event instanceof ArticleUnpublished => [
                'articles.unpublished', $event->actor, ['to' => 'unpublished'],
            ],
        };

        Audit::log($action, $event->article, $properties, $actor);
    }
}
