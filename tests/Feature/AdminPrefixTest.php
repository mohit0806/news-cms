<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Route;

test('admin pages live under the configured prefix', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $this->actingAs($user)->get(route('profile.edit'))->assertOk();

    expect(route('dashboard', absolute: false))->toStartWith('/admin/')
        ->and(route('profile.edit', absolute: false))->toStartWith('/admin/');
});

test('legacy root admin URLs are gone', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertNotFound();
});

test('livewire update endpoint lives under the admin prefix', function () {
    $route = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($r) => $r->getName() === 'admin.livewire.update');

    expect($route)->not->toBeNull();
    expect($route->uri())->toBe('admin/livewire/update');
    expect($route->gatherMiddleware())->toContain('web');
});

test('public homepage sets no cookies', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    expect($response->headers->getCookies())->toBeEmpty();
});
