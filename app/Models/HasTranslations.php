<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shared translation behavior for taxonomy terms (TRD §6.3).
 *
 * `translation()` returns the row for the requested language, falling back
 * to the default language. List queries should eager-load only the needed
 * language: `with(['translations' => fn ($q) => $q->where('language', $lang)])`.
 */
trait HasTranslations
{
    /**
     * @return HasMany<TermTranslation, $this>
     */
    abstract public function translations(): HasMany;

    public function translation(?string $lang = null): ?TermTranslation
    {
        $lang ??= (string) config('app.locale', 'en');

        /** @var Collection<int, TermTranslation> $loaded */
        $loaded = $this->getRelationValue('translations')
            ?? $this->translations()->whereIn('language', [$lang, (string) config('app.locale', 'en')])->get();

        return $loaded->firstWhere('language', $lang)
            ?? $loaded->firstWhere('language', (string) config('app.locale', 'en'));
    }
}
