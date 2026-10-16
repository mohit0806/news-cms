<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\ArticlePublished;
use App\Events\ArticleRejected;
use App\Events\ArticleSubmitted;
use App\Events\ArticleUnpublished;
use App\Listeners\RecordArticleAudit;
use App\Listeners\SendArticleNotifications;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class ArticleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach ([ArticleSubmitted::class, ArticleRejected::class, ArticlePublished::class, ArticleUnpublished::class] as $event) {
            Event::listen($event, RecordArticleAudit::class);
            Event::listen($event, SendArticleNotifications::class);
        }
    }
}
