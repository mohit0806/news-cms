<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Article;
use App\Models\User;

/**
 * Editorial transition signal (P2-T03.4).
 */
final readonly class ArticleUnpublished
{
    public function __construct(public Article $article, public User $actor) {}
}
