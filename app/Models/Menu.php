<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Navigation menu per location and language (PRD §6.6, P2-T08.2).
 *
 * @property int $id
 * @property string $name
 * @property string $location
 * @property string $language
 */
#[Fillable(['name', 'location', 'language'])]
class Menu extends Model
{
    /**
     * @return HasMany<MenuItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort');
    }
}
