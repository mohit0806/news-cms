<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Revision;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Revision primitives for optimistic-concurrency workflows (PRD §6.1.4, TRD §6.4).
 *
 * Implemented as a service (not a trait): PHPStan only analyses traits through
 * their using classes, and the first model consumer arrives in P2 — a trait
 * would ship unanalysed. P2 article editing consumes this contract. Callers
 * authorize record/restore/prune actions before invoking this service.
 */
final class RevisionStore
{
    /**
     * @param  array<string, mixed>  $snapshot
     */
    public static function record(Model $subject, string $kind, array $snapshot, ?int $userId = null): Revision
    {
        if ($kind !== 'manual' && $kind !== 'autosave') {
            throw new InvalidArgumentException("Unknown revision kind [{$kind}].");
        }

        return Revision::create([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'user_id' => $userId,
            'kind' => $kind,
            'snapshot_json' => $snapshot,
        ]);
    }

    /**
     * Restore snapshot fields, recording the pre-restore state as a new manual
     * revision first (restores are themselves reversible).
     *
     * @param  list<string>  $fields  attribute names to restore from the snapshot
     */
    public static function restore(Revision $revision, Model $subject, array $fields, ?int $userId = null): Model
    {
        self::record($subject, 'manual', $subject->only($fields), $userId);

        // snapshot_json is a NOT NULL json column with an array cast, written only
        // as arrays by record(), so it is always an array here.
        $subject->fill(array_intersect_key($revision->snapshot_json, array_flip($fields)));
        $subject->save();

        return $subject;
    }

    /**
     * Delete oldest revisions of one kind beyond the kept count. Returns the
     * number of deleted rows.
     */
    public static function prune(Model $subject, string $kind = 'manual', int $keep = 50): int
    {
        $ids = Revision::query()
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->where('kind', $kind)
            ->orderByDesc('id')
            ->pluck('id')
            ->all();

        $excess = array_slice($ids, max(0, $keep));

        if ($excess === []) {
            return 0;
        }

        return Revision::query()->whereIntegerInRaw('id', $excess)->delete();
    }
}
