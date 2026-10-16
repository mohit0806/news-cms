<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Site time boundaries (TRD §6.1, §11; PRD §8.1, §21.2).
 *
 * Storage and runtime are always UTC. This helper converts to the display
 * timezone only at presentation time. Admin route-level locale enforcement
 * lands with the admin group (P1-T02.2); English-only guard documented there.
 */
final class SiteTime
{
    /**
     * Display timezone identifier (never used for storage).
     */
    public static function siteTimezone(): string
    {
        $timezone = config('app.site_timezone', 'Asia/Kolkata');

        return is_string($timezone) && $timezone !== '' ? $timezone : 'Asia/Kolkata';
    }

    /**
     * Parse and validate a SITE_LANGUAGES list.
     *
     * Interface language (admin UI, English-only in v1) is separate from content
     * languages (article translations). Malformed codes are dropped; an empty
     * result falls back to English. No RTL scope: validated codes are still
     * accepted, but RTL layout is out of scope until an RTL language launches.
     *
     * @return list<string>
     */
    public static function parseLanguages(string $list): array
    {
        $codes = [];

        foreach (explode(',', $list) as $code) {
            $code = trim($code);

            if ($code !== '' && preg_match('/^[a-z]{2,3}(-[a-zA-Z]{2,8})?$/', $code) === 1) {
                $codes[] = $code;
            }
        }

        return $codes === [] ? ['en'] : $codes;
    }

    /**
     * Convert a UTC instant to the display timezone for presentation.
     */
    public static function display(CarbonInterface $date, ?string $timezone = null): CarbonImmutable
    {
        return CarbonImmutable::parse($date)->setTimezone($timezone ?? self::siteTimezone());
    }
}
