<?php

declare(strict_types=1);

use App\Models\User;
use App\Settings\ContentSettings;
use App\Support\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
});

test('successful login records an audit event with request context', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $record = Activity::query()->where('event', 'login.success')->latest('id')->firstOrFail();

    expect($record->causer_id)->toBe($user->id)
        ->and($record->subject_id)->toBe($user->id)
        ->and($record->getProperty('ip'))->not->toBeEmpty();
});

test('failed login records the email but never the password', function () {
    $this->post(route('login.store'), ['email' => 'someone@example.com', 'password' => 'wrong-password']);

    $record = Activity::query()->where('event', 'login.failed')->latest('id')->firstOrFail();
    $stored = json_encode($record->properties);

    expect($record->getProperty('email'))->toBe('someone@example.com')
        ->and($stored)->not->toContain('wrong-password');
});

test('user updates record masked changes', function () {
    $admin = User::factory()->create();
    $admin->assignRole('Admin');

    $user = User::factory()->create();
    $this->actingAs($admin);

    $user->forceFill(['name' => 'Renamed', 'password' => 'new-secret-password-10'])->save();

    $record = Activity::query()->where('event', 'users.updated')->latest('id')->firstOrFail();
    $stored = json_encode($record->properties);

    expect($stored)->toContain('Renamed')
        ->and($stored)->not->toContain('new-secret-password-10')
        ->and($stored)->toContain('[masked]');
});

test('role assignment to a user is audited', function () {
    $user = User::factory()->create();
    $user->assignRole('Reporter');

    expect(Activity::query()->where('event', 'users.role-assigned')->where('subject_id', $user->id)->exists())->toBeTrue();
});

test('audit records cannot be updated or deleted through the model', function () {
    $user = User::factory()->create();

    $record = Audit::log('login.success', $user, [], $user);
    assert($record instanceof Activity);

    expect(fn () => $record->update(['description' => 'rewritten']))->toThrow(RuntimeException::class);
    expect(fn () => $record->delete())->toThrow(RuntimeException::class);
    expect(Activity::query()->whereKey($record->getKey())->exists())->toBeTrue();
});

test('pruning deletes only rows older than retention', function () {
    $user = User::factory()->create();

    Audit::log('login.success', $user, [], $user);
    DB::table('activity_log')->insert([
        'log_name' => 'default',
        'description' => 'old',
        'event' => 'login.success',
        'created_at' => now()->subDays(400)->toDateTimeString(),
        'updated_at' => now()->subDays(400)->toDateTimeString(),
    ]);

    $deleted = Audit::prune(365);

    expect($deleted)->toBe(1)
        ->and(Activity::query()->where('event', 'login.success')->where('description', '!=', 'old')->exists())->toBeTrue();
});

test('settings saves emit a group-only audit event', function () {
    $settings = app(ContentSettings::class);
    $settings->fill([
        'require_featured_image' => true,
        'require_alt_text' => true,
        'articles_per_page' => 20,
        'trash_retention_days' => 30,
        'revision_count' => 50,
        'breaking_default_hours' => 2,
    ]);
    $settings->save();

    $record = Activity::query()->where('event', 'settings.changed')->latest('id')->firstOrFail();
    $stored = json_encode($record->properties);

    expect($stored)->toContain('content')
        ->and($stored)->not->toContain('articles_per_page');
});

test('audit failure is loud and never blocks the primary action', function () {
    Log::spy();

    DB::statement('ALTER TABLE activity_log RENAME TO activity_log_hold');

    try {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $response->assertRedirect(route('dashboard', absolute: false));
        Log::shouldHaveReceived('critical')->atLeast()->once();
    } finally {
        DB::statement('ALTER TABLE activity_log_hold RENAME TO activity_log');
    }
});
