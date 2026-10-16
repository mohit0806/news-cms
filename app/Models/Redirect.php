<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Exact/wildcard redirect row (P2-T01.3 schema; admin UI + public resolver in P3-T05).
 *
 * Written by tag merges; read by the future path resolver before resolution.
 *
 * @property int $id
 * @property string $from_path
 * @property string $to_url
 * @property int $status_code
 * @property bool $is_wildcard
 */
#[Fillable(['from_path', 'to_url', 'status_code', 'is_wildcard', 'created_by'])]
class Redirect extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_wildcard' => 'boolean',
        ];
    }
}
