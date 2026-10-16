<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Article;
use App\Models\User;

/**
 * Editorial transition signal (P2-T03.4).
 *
 * Dispatched after the transition commits. Listeners (audit, notifications)
 * run synchronously in P2; P3 adds cache-purge listeners.
 */
final readonly class ArticleSubmitted
{
    public function __construct(public Article $article, public User $actor) {}
}
