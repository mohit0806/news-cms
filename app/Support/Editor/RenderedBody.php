<?php

declare(strict_types=1);

namespace App\Support\Editor;

/**
 * Rendered article body (TRD §8.3; P2-T04.1).
 */
final readonly class RenderedBody
{
    /**
     * @param  list<int|string>  $mediaIds
     * @param  list<string>  $embeds
     */
    public function __construct(
        public string $html,
        public string $text,
        public int $readingTimeMin,
        public array $mediaIds = [],
        public array $embeds = [],
    ) {}
}
