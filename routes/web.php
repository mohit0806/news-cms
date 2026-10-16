<?php

use App\Http\Controllers\ArticlesController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthorsController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\Invitations\AcceptInvitationController;
use App\Http\Controllers\Invitations\StoreInvitationController;
use App\Http\Controllers\MenusController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SystemStatusController;
use App\Http\Controllers\TagsController;
use App\Http\Controllers\UsersController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::view('/', 'welcome')->name('home')->withoutMiddleware([
    // Public pages must not start sessions or set cookies (PRD D12).
    // Narrow precursor to P3's cms.public group, which owns the full split.
    StartSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
]);

Route::prefix(config('app.admin_path', 'admin'))->middleware(['auth', 'ensure-active', 'ensure-2fa'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('system-status', SystemStatusController::class)->middleware('can:system.view')->name('system.status');
    Route::post('users/invitations', StoreInvitationController::class)->middleware('can:users.manage')->name('invitations.store');
    Route::get('users', [UsersController::class, 'index'])->middleware('can:users.manage')->name('users.index');
    Route::get('users/create', [UsersController::class, 'create'])->middleware('can:users.manage')->name('users.create');
    Route::post('users', [UsersController::class, 'store'])->middleware('can:users.manage')->name('users.store');
    Route::post('users/bulk-deactivate', [UsersController::class, 'bulkDeactivate'])->middleware('can:users.manage')->name('users.bulk-deactivate');
    Route::get('users/{user}/edit', [UsersController::class, 'edit'])->middleware('can:users.manage')->name('users.edit');
    Route::put('users/{user}', [UsersController::class, 'update'])->middleware('can:users.manage')->name('users.update');
    Route::post('users/{user}/deactivate', [UsersController::class, 'deactivate'])->middleware('can:users.manage')->name('users.deactivate');
    Route::post('users/{user}/activate', [UsersController::class, 'activate'])->middleware('can:users.manage')->name('users.activate');
    Route::get('roles', [RolesController::class, 'index'])->middleware('can:roles.manage')->name('roles.index');
    Route::get('categories', [CategoriesController::class, 'index'])->middleware('can:categories.manage')->name('categories.index');
    Route::get('categories/create', [CategoriesController::class, 'create'])->middleware('can:categories.manage')->name('categories.create');
    Route::post('categories', [CategoriesController::class, 'store'])->middleware('can:categories.manage')->name('categories.store');
    Route::get('categories/{category}/edit', [CategoriesController::class, 'edit'])->middleware('can:categories.manage')->name('categories.edit');
    Route::put('categories/{category}', [CategoriesController::class, 'update'])->middleware('can:categories.manage')->name('categories.update');
    Route::delete('categories/{category}', [CategoriesController::class, 'destroy'])->middleware('can:categories.manage')->name('categories.destroy');
    Route::get('tags', [TagsController::class, 'index'])->middleware('can:tags.manage')->name('tags.index');
    Route::get('tags/create', [TagsController::class, 'create'])->middleware('can:tags.manage')->name('tags.create');
    Route::post('tags', [TagsController::class, 'store'])->middleware('can:tags.manage')->name('tags.store');
    Route::get('tags/merge', [TagsController::class, 'mergeForm'])->middleware('can:tags.manage', 'can:categories.manage')->name('tags.merge');
    Route::post('tags/merge', [TagsController::class, 'merge'])->middleware('can:tags.manage', 'can:categories.manage')->name('tags.merge.store');
    Route::delete('tags/{tag}', [TagsController::class, 'destroy'])->middleware('can:tags.manage', 'can:categories.manage')->name('tags.destroy');
    Route::get('authors', [AuthorsController::class, 'index'])->middleware('can:authors.manage')->name('authors.index');
    Route::get('authors/create', [AuthorsController::class, 'create'])->middleware('can:authors.manage')->name('authors.create');
    Route::post('authors', [AuthorsController::class, 'store'])->middleware('can:authors.manage')->name('authors.store');
    Route::get('authors/{author}/edit', [AuthorsController::class, 'edit'])->middleware('can:authors.manage')->name('authors.edit');
    Route::put('authors/{author}', [AuthorsController::class, 'update'])->middleware('can:authors.manage')->name('authors.update');
    Route::post('authors/{author}/deactivate', [AuthorsController::class, 'deactivate'])->middleware('can:authors.manage')->name('authors.deactivate');
    Route::post('authors/{author}/activate', [AuthorsController::class, 'activate'])->middleware('can:authors.manage')->name('authors.activate');
    Route::delete('authors/{author}', [AuthorsController::class, 'destroy'])->middleware('can:authors.manage')->name('authors.destroy');
    Route::get('pages', [PagesController::class, 'index'])->middleware('can:pages.manage')->name('pages.index');
    Route::get('pages/create', [PagesController::class, 'create'])->middleware('can:pages.manage')->name('pages.create');
    Route::post('pages', [PagesController::class, 'store'])->middleware('can:pages.manage')->name('pages.store');
    Route::get('pages/{page}/edit', [PagesController::class, 'edit'])->middleware('can:pages.manage')->name('pages.edit');
    Route::put('pages/{page}', [PagesController::class, 'update'])->middleware('can:pages.manage')->name('pages.update');
    Route::delete('pages/{page}', [PagesController::class, 'destroy'])->middleware('can:pages.manage')->name('pages.destroy');
    Route::get('menus', [MenusController::class, 'index'])->middleware('can:menus.manage')->name('menus.index');
    Route::get('menus/create', [MenusController::class, 'create'])->middleware('can:menus.manage')->name('menus.create');
    Route::post('menus', [MenusController::class, 'store'])->middleware('can:menus.manage')->name('menus.store');
    Route::get('menus/{menu}/edit', [MenusController::class, 'edit'])->middleware('can:menus.manage')->name('menus.edit');
    Route::put('menus/{menu}', [MenusController::class, 'update'])->middleware('can:menus.manage')->name('menus.update');
    Route::delete('menus/{menu}', [MenusController::class, 'destroy'])->middleware('can:menus.manage')->name('menus.destroy');
    Route::post('menus/{menu}/items', [MenusController::class, 'storeItem'])->middleware('can:menus.manage')->name('menus.items.store');
    Route::post('menus/{menu}/items/reorder', [MenusController::class, 'reorder'])->middleware('can:menus.manage')->name('menus.items.reorder');
    Route::delete('menus/{menu}/items/{item}', [MenusController::class, 'destroyItem'])->middleware('can:menus.manage')->name('menus.items.destroy');
    Route::get('homepage', [HomepageController::class, 'index'])->middleware('can:homepage.manage')->name('homepage.index');
    Route::get('homepage/create', [HomepageController::class, 'create'])->middleware('can:homepage.manage')->name('homepage.create');
    Route::post('homepage', [HomepageController::class, 'store'])->middleware('can:homepage.manage')->name('homepage.store');
    Route::get('homepage/{block}/edit', [HomepageController::class, 'edit'])->middleware('can:homepage.manage')->name('homepage.edit');
    Route::put('homepage/{block}', [HomepageController::class, 'update'])->middleware('can:homepage.manage')->name('homepage.update');
    Route::delete('homepage/{block}', [HomepageController::class, 'destroy'])->middleware('can:homepage.manage')->name('homepage.destroy');
    Route::post('homepage/reorder', [HomepageController::class, 'reorder'])->middleware('can:homepage.manage')->name('homepage.reorder');
    Route::get('articles', [ArticlesController::class, 'index'])->middleware('can:viewAny,App\Models\Article')->name('articles.index');
    Route::get('articles/create', [ArticlesController::class, 'create'])->middleware('can:create,App\Models\Article')->name('articles.create');
    Route::post('articles', [ArticlesController::class, 'store'])->middleware('can:create,App\Models\Article')->name('articles.store');
    Route::get('articles/{article}/edit', [ArticlesController::class, 'edit'])->middleware('can:update,article')->name('articles.edit');
    Route::put('articles/{article}', [ArticlesController::class, 'update'])->middleware('can:update,article')->name('articles.update');
    Route::delete('articles/{article}', [ArticlesController::class, 'destroy'])->middleware('can:delete,article')->name('articles.destroy');
    Route::post('articles/{id}/restore', [ArticlesController::class, 'restore'])->middleware('can:articles.delete')->name('articles.restore');
    Route::post('articles/{article}/autosave', [ArticlesController::class, 'autosave'])->middleware('can:update,article')->name('articles.autosave');
    Route::post('articles/{article}/submit', [ArticlesController::class, 'submit'])->middleware('can:submit,article')->name('articles.submit');
    Route::post('articles/{article}/reject', [ArticlesController::class, 'reject'])->middleware('can:articles.publish')->name('articles.reject');
    Route::post('articles/{article}/publish', [ArticlesController::class, 'publish'])->middleware('can:articles.publish')->name('articles.publish');
    Route::post('articles/{article}/unpublish', [ArticlesController::class, 'unpublish'])->middleware('can:articles.unpublish')->name('articles.unpublish');
    Route::get('audit-log', [AuditLogController::class, 'index'])->middleware('can:audit.view')->name('audit.index');
    Route::get('audit-log/export', [AuditLogController::class, 'export'])->middleware('can:audit.view')->name('audit.export');
    Route::get('settings', [SettingsController::class, 'index'])->middleware('can:settings.manage')->name('settings.index');
    // NOTE: the `group/` infix avoids shadowing the starter-kit Livewire settings
    // pages (`settings/profile`, `settings/appearance`, `settings/security`).
    // The whereIn backstop keeps unknown slugs from ever matching here.
    Route::get('settings/group/{group}', [SettingsController::class, 'edit'])->middleware('can:settings.manage')->whereIn('group', array_keys(SettingsController::groups()))->name('settings.edit');
    Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('notifications/preferences', [NotificationsController::class, 'updatePreferences'])->name('notifications.preferences');
    Route::post('notifications/read-all', [NotificationsController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationsController::class, 'markRead'])->name('notifications.read');
    Route::put('settings/group/{group}', [SettingsController::class, 'update'])->middleware('can:settings.manage')->whereIn('group', array_keys(SettingsController::groups()))->name('settings.update');
});

Route::prefix('invitations')->name('invitations.')->middleware('signed')->group(function () {
    Route::get('accept/{invitation}', [AcceptInvitationController::class, 'show'])->name('accept');
    Route::post('accept/{invitation}', [AcceptInvitationController::class, 'accept'])->name('accept.store');
});

require __DIR__.'/settings.php';
