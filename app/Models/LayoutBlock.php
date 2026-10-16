<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Homepage content block (PRD §6.7/§20, P2-T08.3).
 *
 * Manual picks always win; unpublished/deleted manual picks are skipped at
 * render time (P3). Every mutation dispatches HomepageUpdated (purge contract).
 *
 * @property int $id
 * @property string $region
 * @property string $language
 * @property string $widget
 * @property string|null $title
 * @property array<string, mixed>|null $config_json
 * @property int $sort
 * @property bool $is_active
 */
#[Fillable(['region', 'language', 'widget', 'title', 'config_json', 'sort', 'is_active'])]
class LayoutBlock extends Model
{
    /**
     * Block widgets (PRD §6.7).
     *
     * @var list<string>
     */
    public const WIDGETS = [
        'lead', 'top-stories', 'category-section', 'latest',
        'trending', 'gallery-section', 'video-section', 'ad-slot', 'custom-html',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config_json' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
