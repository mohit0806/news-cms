<?php

use Illuminate\Support\Facades\Route;

Route::prefix(config('app.admin_path', 'admin'))->middleware(['auth', 'ensure-active', 'ensure-2fa'])->group(function () {
    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::prefix(config('app.admin_path', 'admin'))->middleware(['auth', 'ensure-active', 'ensure-2fa'])->group(function () {
    Route::livewire('settings/appearance', 'pages::settings.appearance')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});
