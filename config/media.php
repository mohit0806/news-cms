<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Media storage
    |--------------------------------------------------------------------------
    |
    | Code uses Laravel Storage only (PRD §7.4). The Media Library reads these
    | values through AppServiceProvider wiring; MEDIA_DISK also feeds the
    | package's own `disk_name` default.
    |
    */
    'disk' => env('MEDIA_DISK', 'public'),

    'cdn_url' => env('MEDIA_CDN_URL'),

    'queue' => env('MEDIA_QUEUE', ''),

    /*
    |--------------------------------------------------------------------------
    | Upload limits in megabytes (PRD §7.2). PHP/Nginx caps still apply on top:
    | files above upload_max_filesize/post_max_size never reach validation.
    |
    */
    'max_upload_mb' => [
        'image' => (int) env('MAX_UPLOAD_IMAGE_MB', 20),
        'document' => (int) env('MAX_UPLOAD_DOC_MB', 25),
        'audio' => (int) env('MAX_UPLOAD_AUDIO_MB', 50),
        'video' => (int) env('MAX_UPLOAD_VIDEO_MB', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed extensions per type (PRD §7.2). Blocked types (svg, html, php,
    | executables, archives) are rejected regardless of extension or MIME.
    |
    */
    'allowed_extensions' => [
        'image' => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'],
        'document' => ['pdf'],
        'audio' => ['mp3', 'm4a'],
        'video' => ['mp4', 'webm'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image pipeline (PRD §7.3). Responsive widths + quality; named crops use
    | the focal point (TRD §10.2). AVIF registers only when the runtime
    | encoder exists (S9); detection lives in App\Support\Media\ImageCodecs.
    |
    */
    'responsive_widths' => [320, 640, 1024, 1600],

    'conversion_quality' => 80,

    'crops' => [
        'thumb' => ['width' => 300, 'height' => 300],
        'card' => ['width' => 640, 'height' => 360],
        'hero' => ['width' => 1600, 'height' => 900],
    ],

    'og' => ['width' => 1200, 'height' => 630],
];
