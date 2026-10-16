<?php

use App\Providers\AppServiceProvider;
use App\Providers\ArticleServiceProvider;
use App\Providers\AuditServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    ArticleServiceProvider::class,
    AuditServiceProvider::class,
    AuthServiceProvider::class,
    FortifyServiceProvider::class,
];
