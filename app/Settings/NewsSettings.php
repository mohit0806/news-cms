<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Base class for NewsCMS typed settings (PRD §15.6, ADR-0004).
 *
 * Conventions: typed public properties with safe defaults; `rules()` documents
 * the validation enforced by admin forms (T03.3); `owner()` names the capability
 * that owns the group. Language-scoped values are arrays keyed by language code.
 */
abstract class NewsSettings extends Settings
{
    /**
     * Capability that owns this group.
     */
    public static function owner(): string
    {
        return 'System';
    }

    /**
     * Validation rules enforced by admin forms, keyed by property path.
     *
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [];
    }
}
