<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Minimal system-status probes (PRD §21.3 subset for the P1 admin shell).
 *
 * Every check probes instead of hardcoding; failures report instead of
 * throwing. Permission gating (`system.view`) lands with P1-T01.6.
 */
final class SystemStatus
{
    /**
     * @return list<array{label: string, ok: bool, detail: string}>
     */
    public static function checks(): array
    {
        return [
            self::check('PHP version', fn (): string => PHP_VERSION, version_compare(PHP_VERSION, '8.3.0', '>=')),
            self::check('Laravel version', fn (): string => app()->version(), true),
            self::check(
                'Database connection',
                fn (): string => (string) DB::connection()->getDatabaseName(),
                self::probe(function (): bool {
                    DB::select('SELECT 1');

                    return true;
                }),
            ),
            self::check(
                'Pending migrations',
                function (): string {
                    $files = count(glob(database_path('migrations/*.php')) ?: []);
                    $ran = DB::table('migrations')->count();

                    return "{$ran} applied / {$files} files";
                },
                self::probe(fn (): bool => true),
            ),
            self::check(
                'Failed jobs',
                fn (): string => (string) DB::table('failed_jobs')->count(),
                self::probe(fn (): bool => true),
            ),
            self::check(
                'Redis ping',
                fn (): string => self::redisPing(),
                self::redisPing() !== 'unreachable',
            ),
            self::check(
                'Disk free',
                function (): string {
                    $free = disk_free_space('/');
                    $total = disk_total_space('/');

                    if ($free === false || $total === false || $total <= 0) {
                        return 'unknown';
                    }

                    return round($free / 1024 / 1024 / 1024, 1).' GB free ('.round(100 * $free / $total).'%)';
                },
                self::probe(function (): bool {
                    $free = disk_free_space('/');
                    $total = disk_total_space('/');

                    return $free !== false && $total !== false && $total > 0 && $free / $total > 0.2;
                }),
            ),
        ];
    }

    /**
     * @param  callable(): string  $detail
     * @return array{label: string, ok: bool, detail: string}
     */
    private static function check(string $label, callable $detail, bool $ok): array
    {
        try {
            $text = $detail();
        } catch (Throwable) {
            $text = 'unavailable';
            $ok = false;
        }

        return ['label' => $label, 'ok' => $ok, 'detail' => $text];
    }

    private static function probe(callable $probe): bool
    {
        try {
            return (bool) $probe();
        } catch (Throwable) {
            return false;
        }
    }

    private static function redisPing(): string
    {
        try {
            Redis::connection()->ping();

            return 'PONG';
        } catch (Throwable) {
            return 'unreachable';
        }
    }
}
