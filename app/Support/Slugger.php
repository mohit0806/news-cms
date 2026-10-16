<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Slug generation (PRD §8.3; TRD §11; P2-T06.1).
 *
 * Default mode transliterates to Latin via ICU (`Any-Latin; Latin-ASCII`),
 * lowercases, hyphenates, and trims to 80 characters at a word boundary.
 * With `UNICODE_SLUGS=true` the native script is kept instead (NFC-normalized;
 * letters, marks, and digits survive, everything else becomes a hyphen).
 * Collision suffixes (`-2`, `-3`, …) are appended by `unique()`.
 */
final class Slugger
{
    /**
     * Generate a Latin slug from any title.
     */
    public static function slug(string $text): string
    {
        $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII');

        $latin = $transliterator === null
            ? $text
            : $transliterator->transliterate($text);

        $slug = strtolower((string) $latin);
        $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        return self::truncate($slug);
    }

    /**
     * Generate a native-script slug (UNICODE_SLUGS mode).
     */
    public static function slugUnicode(string $text): string
    {
        $normalized = \Normalizer::normalize($text, \Normalizer::FORM_C);

        $slug = mb_strtolower($normalized === false ? $text : $normalized);
        $slug = (string) preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', $slug);
        $slug = trim($slug, '-');

        return self::truncate($slug);
    }

    /**
     * Dispatch on the `app.unicode_slugs` install option.
     */
    public static function for(string $text, ?bool $unicode = null): string
    {
        $unicode ??= (bool) config('app.unicode_slugs', false);

        return $unicode ? self::slugUnicode($text) : self::slug($text);
    }

    /**
     * Append `-2`, `-3`, … until `$taken` reports the candidate free.
     *
     * @param  callable(string): bool  $taken
     */
    public static function unique(string $base, callable $taken): string
    {
        if (! $taken($base)) {
            return $base;
        }

        $counter = 2;

        do {
            $suffix = '-'.$counter;
            $candidate = self::truncate($base, 80 - strlen($suffix)).$suffix;
            $counter++;
        } while ($taken($candidate));

        return $candidate;
    }

    /**
     * Trim to $max characters at a word (hyphen) boundary.
     * Multibyte-aware: native-script slugs count characters, not bytes.
     */
    private static function truncate(string $slug, int $max = 80): string
    {
        if (mb_strlen($slug) <= $max) {
            return $slug;
        }

        $cut = mb_substr($slug, 0, $max);
        $boundary = mb_strrpos($cut, '-');

        if ($boundary === false || $boundary === 0) {
            return rtrim($cut, '-');
        }

        return mb_substr($cut, 0, $boundary);
    }
}
