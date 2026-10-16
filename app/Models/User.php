<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Media\ImageCodecs;
use BackedEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property bool $is_active
 * @property string|null $locale
 * @property bool $email_notifications
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, InteractsWithMedia, Notifiable, TwoFactorAuthenticatable;

    use HasRoles {
        HasRoles::assignRole as protected assignRoleTrait;
        HasRoles::syncRoles as protected syncRolesTrait;
    }

    /**
     * Assign the user's single role, replacing any existing one (PRD §4.1:
     * each user has exactly one role).
     *
     * @param  string|int|array<mixed>|Role|Collection<array-key, mixed>|BackedEnum  ...$roles
     */
    public function assignRole(...$roles): static
    {
        $roles = collect($roles)->flatten()->all();

        if (count($roles) !== 1) {
            throw new InvalidArgumentException('Users hold exactly one role.');
        }

        // Replace (not attach): the original syncRoles() delegates back to
        // assignRole(), so delegating there from here would recurse.
        if ($this->exists) {
            $this->roles()->detach();
            $this->unsetRelation('roles');
        }

        return $this->assignRoleTrait($roles);
    }

    /**
     * Sync roles with the same exactly-one-role invariant.
     *
     * @param  string|int|array<mixed>|Role|Collection<array-key, mixed>|BackedEnum  ...$roles
     */
    public function syncRoles(...$roles): static
    {
        $roles = collect($roles)->flatten()->all();

        if (count($roles) !== 1) {
            throw new InvalidArgumentException('Users hold exactly one role.');
        }

        return $this->syncRolesTrait($roles);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'email_notifications' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Session, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'user_id');
    }

    /**
     * Media collections, one per upload type (P2-T05.3).
     *
     * Library uploads attach to the uploading staff member (PRD §7.1
     * uploader filter); folder assignment lives in NewsCMS-owned
     * `media_folder_media`, never in package columns (ADR-0001).
     */
    public function registerMediaCollections(): void
    {
        foreach (['library-image', 'library-document', 'library-audio', 'library-video'] as $collection) {
            $this->addMediaCollection($collection);
        }
    }

    /**
     * Image conversions (PRD §7.3; TRD §10.2). Images only — documents,
     * audio, and video live in sibling collections with no conversions.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $quality = (int) config('media.conversion_quality', 80);

        foreach (config('media.responsive_widths', []) as $width) {
            $width = (int) $width;

            $this->addMediaConversion("w-{$width}")
                ->performOnCollections('library-image')
                ->width($width)
                ->format('webp')
                ->quality($quality);

            if (ImageCodecs::supportsAvif()) {
                $this->addMediaConversion("w-{$width}-avif")
                    ->performOnCollections('library-image')
                    ->width($width)
                    ->format('avif')
                    ->quality($quality);
            }
        }

        foreach (config('media.crops', []) as $name => $crop) {
            $this->addMediaConversion((string) $name)
                ->performOnCollections('library-image')
                ->focalCrop(
                    (int) $crop['width'],
                    (int) $crop['height'],
                    self::focalCenterX($media, (int) $crop['width']),
                    self::focalCenterY($media, (int) $crop['height']),
                )
                ->format('webp')
                ->quality($quality);
        }

        $og = config('media.og', ['width' => 1200, 'height' => 630]);

        $this->addMediaConversion('og')
            ->performOnCollections('library-image')
            ->focalCrop(
                (int) $og['width'],
                (int) $og['height'],
                self::focalCenterX($media, (int) $og['width']),
                self::focalCenterY($media, (int) $og['height']),
            )
            ->format('jpeg')
            ->quality($quality);
    }

    /**
     * Focal center in pixels, clamped inside the original (TRD §10.2).
     */
    private static function focalCenterX(?Media $media, int $targetWidth): int
    {
        $width = (int) ($media?->getCustomProperty('width', 0));
        $x = (int) round($width * ((float) ($media?->getCustomProperty('focal_x', 50)) / 100));

        return max((int) ($targetWidth / 2), min($x, $width - (int) ($targetWidth / 2)));
    }

    /**
     * Focal center in pixels, clamped inside the original (TRD §10.2).
     */
    private static function focalCenterY(?Media $media, int $targetHeight): int
    {
        $height = (int) ($media?->getCustomProperty('height', 0));
        $y = (int) round($height * ((float) ($media?->getCustomProperty('focal_y', 50)) / 100));

        return max((int) ($targetHeight / 2), min($y, $height - (int) ($targetHeight / 2)));
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
