<?php

declare(strict_types=1);

namespace App\Events;

/**
 * Homepage composition changed (P2-T08.3 purge contract).
 *
 * P3 wires the cache purger to this event; no listeners exist in P2.
 */
final readonly class HomepageUpdated
{
    public function __construct(public string $language) {}
}
