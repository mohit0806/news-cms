<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Slugs the public router owns (PRD §8.2; P2-T01.2).
 *
 * Categories and pages may not claim these; P3's path resolver assumes they
 * resolve to reserved routes first. The current ADMIN_PATH is always reserved.
 */
final class ReservedSlugs
{
    /**
     * @var list<string>
     */
    private const RESERVED = [
        'admin', 'search', 'tag', 'author', 'gallery', 'feed', 'sitemap',
        'preview', 'live', 'poll', 't', 'api', 'storage', 'assets', 'build',
    ];

    /**
     * @return list<string>
     */
    public static function list(): array
    {
        $slugs = self::RESERVED;
        $adminPath = (string) config('app.admin_path', 'admin');

        if (! in_array($adminPath, $slugs, true)) {
            $slugs[] = $adminPath;
        }

        return $slugs;
    }

    public static function isReserved(string $slug): bool
    {
        return in_array(strtolower($slug), self::list(), true);
    }
}
