<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class ConflictException extends RuntimeException
{
    /**
     * Build the conflict message equivalent to "Changed by {user} at {time}".
     * Only safe, caller-supplied values are included — never raw row contents.
     */
    public static function stale(
        string $subject,
        int $expectedVersion,
        int|string|null $currentVersion,
        ?string $changedBy,
        string $at,
    ): self {
        $prefix = $changedBy !== null && $changedBy !== ''
            ? "Changed by {$changedBy} at {$at}"
            : "Changed at {$at}";

        return new self("{$prefix} ({$subject} is at version {$currentVersion}, expected {$expectedVersion}).");
    }
}
