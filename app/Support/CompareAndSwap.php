<?php

declare(strict_types=1);

namespace App\Support;

use App\Exceptions\ConflictException;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Atomic compare-and-swap updates against a `row_version` column (TRD §6.4).
 *
 * A single UPDATE constrained by the expected version makes competing writes
 * fail instead of silently overwriting each other. Callers authorize the save;
 * this helper only enforces the version contract.
 */
final class CompareAndSwap
{
    /**
     * @template TSubject of Model
     *
     * @param  TSubject  $subject
     * @param  array<string, mixed>  $attributes
     * @return TSubject
     *
     * @throws ConflictException
     */
    public static function attempt(
        Model $subject,
        int $expectedVersion,
        array $attributes,
        string $column = 'row_version',
        ?string $changedBy = null,
    ): Model {
        $updated = $subject->newQuery()
            ->whereKey($subject->getKey())
            ->where($column, $expectedVersion)
            ->update(array_merge($attributes, [$column => $expectedVersion + 1]));

        if ($updated !== 0) {
            // The row was just written by this statement, so it exists.
            return $subject->refresh();
        }

        $fresh = $subject->fresh();

        $currentVersion = $fresh?->getAttribute($column);
        $currentVersion = is_int($currentVersion) || is_string($currentVersion) ? $currentVersion : 'unknown';

        $updatedAt = $fresh?->getAttribute('updated_at');
        $at = $updatedAt instanceof CarbonInterface ? $updatedAt->toDateTimeString() : 'unknown time';

        throw ConflictException::stale(
            $subject->getMorphClass(),
            $expectedVersion,
            $currentVersion,
            $changedBy,
            $at,
        );
    }
}
