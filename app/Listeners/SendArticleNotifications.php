<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ArticlePublished;
use App\Events\ArticleRejected;
use App\Events\ArticleSubmitted;
use App\Events\ArticleUnpublished;
use App\Models\Article;
use App\Models\User;
use App\Notifications\ArticleStatusNotification;
use App\Support\Notifications;
use Illuminate\Contracts\Events\ShouldBeDiscovered;
use Illuminate\Database\Eloquent\Collection;

/**
 * Bell + email fan-out for editorial transitions (PRD §15.4; P2-T03.4).
 *
 * Synchronous listeners: P1's `sendOnce()` dedupe proof covers synchronous
 * redelivery, and double event handling collapses while the first row is
 * unread. Email follows each user's opt-in (off by default).
 */
class SendArticleNotifications implements ShouldBeDiscovered
{
    public static function shouldBeDiscovered(): bool
    {
        return false;
    }

    public function handle(
        ArticleSubmitted|ArticleRejected|ArticlePublished|ArticleUnpublished $event
    ): void {
        $article = $event->article;

        [$recipients, $notificationEvent, $note] = match (true) {
            $event instanceof ArticleSubmitted => [
                $this->publishHolders(),
                'submitted',
                null,
            ],
            $event instanceof ArticleRejected => [
                $this->authorOf($article),
                'rejected',
                $event->note,
            ],
            $event instanceof ArticlePublished => [
                $this->authorOf($article)->merge($this->publishHolders()),
                'published',
                null,
            ],
            $event instanceof ArticleUnpublished => [
                $this->authorOf($article),
                'unpublished',
                null,
            ],
        };

        foreach ($recipients as $user) {
            Notifications::sendOnce(
                $user,
                "article:{$article->getKey()}:{$notificationEvent}",
                new ArticleStatusNotification(
                    $notificationEvent,
                    (int) $article->getKey(),
                    (string) $article->title,
                    "article:{$article->getKey()}:{$notificationEvent}",
                    $note,
                )
            );
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function authorOf(Article $article): Collection
    {
        $author = User::query()->whereKey($article->created_by)->first();

        return $author instanceof User
            ? new Collection([$author])
            : new Collection;
    }

    /**
     * @return Collection<int, User>
     */
    private function publishHolders(): Collection
    {
        return User::permission('articles.publish')->get();
    }
}
