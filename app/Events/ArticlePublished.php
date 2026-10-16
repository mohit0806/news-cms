<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Article;
use App\Models\User;

/**
 * Editorial transition signal (P2-T03.4). A null actor means the scheduler.
 */
final readonly class ArticlePublished
{
    public function __construct(public Article $article, public ?User $actor) {}
}
