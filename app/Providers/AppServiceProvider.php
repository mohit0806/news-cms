<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Media\UuidPathGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureLivewireUpdateRoute();
        $this->configureLoginRedirect();
        $this->configureLoginTracking();
        $this->configureMediaLibrary();
    }

    /**
     * Point the Media Library at NewsCMS media settings (P2-T05.3).
     *
     * Storage paths, disk, and conversion queue come from `config/media.php`
     * (env-driven); the package's own config file stays unpublished.
     */
    protected function configureMediaLibrary(): void
    {
        config([
            'media-library.path_generator' => UuidPathGenerator::class,
            'media-library.disk_name' => config('media.disk', 'public'),
            'media-library.queue_name' => config('media.queue', ''),
        ]);
    }

    /**
     * Record successful logins for audit and session management (PRD §16).
     */
    protected function configureLoginTracking(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            $user = $event->user;

            if ($user instanceof Model) {
                $user->forceFill(['last_login_at' => now()])->save();
            }
        });
    }

    /**
     * Redirect post-login flows to the admin dashboard under the configured
     * prefix (Fortify's static `home` cannot follow ADMIN_PATH).
     */
    protected function configureLoginRedirect(): void
    {
        $redirect = new class implements LoginResponse, TwoFactorLoginResponse
        {
            public function toResponse($request): mixed
            {
                return redirect()->intended(route('dashboard', absolute: false));
            }
        };

        $this->app->singleton(LoginResponse::class, fn (): LoginResponse => $redirect);
        $this->app->singleton(TwoFactorLoginResponse::class, fn (): TwoFactorLoginResponse => $redirect);
    }

    /**
     * Serve Livewire updates under the admin prefix so the path-scoped admin
     * session cookie reaches them (P0-T06 S3 verdict; PRD D12, TRD §5.2).
     */
    protected function configureLivewireUpdateRoute(): void
    {
        $prefix = trim((string) config('app.admin_path', 'admin'), '/');

        Livewire::setUpdateRoute(
            fn ($handle) => Route::post("/{$prefix}/livewire/update", $handle)->name('admin.livewire.update')
        );
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // PRD §15.5 fixes the minimum at 10 characters in every environment
        // (resolves the starter-kit's stricter production default).
        Password::defaults(fn (): Password => Password::min(10));
    }
}
