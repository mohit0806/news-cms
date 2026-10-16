<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Queue;

class TestPingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly ?string $dedupeKey = null) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable->email_notifications ? ['mail', 'database'] : ['database'];
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return array_filter([
            'message' => 'Ping',
            'dedupe_key' => $this->dedupeKey,
        ], fn ($value) => $value !== null);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Ping')->line('Ping');
    }
}

test('notifications index renders for authenticated users', function () {
    // Guest first: actingAs() persists for later calls within one test.
    $this->get(route('notifications.index'))->assertRedirect(route('login'));

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('notifications.index'))->assertOk();
});

test('email preference defaults off and toggles persist', function () {
    $user = User::factory()->create();

    expect($user->email_notifications)->toBeFalse();

    $this->actingAs($user)->post(route('notifications.preferences'), ['email_notifications' => '1']);

    expect($user->refresh()->email_notifications)->toBeTrue();

    $this->actingAs($user)->post(route('notifications.preferences'), []);

    expect($user->refresh()->email_notifications)->toBeFalse();
});

test('marking another user notification as read is forbidden', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    NotificationFacade::send($owner, new TestPingNotification);
    $notification = $owner->notifications()->firstOrFail();

    $this->actingAs($other)->post(route('notifications.read', $notification))->assertForbidden();

    expect($notification->refresh()->read_at)->toBeNull();

    $this->actingAs($owner)->post(route('notifications.read', $notification))->assertRedirect();

    expect($notification->refresh()->read_at)->not->toBeNull();
});

test('email channel routes only when the preference is on', function () {
    $off = User::factory()->create(['email_notifications' => false]);
    $on = User::factory()->create(['email_notifications' => true]);

    expect((new TestPingNotification)->via($off))->toBe(['database'])
        ->and((new TestPingNotification)->via($on))->toBe(['mail', 'database'])
        ->and((new TestPingNotification)->toMail($on)->subject)->toBe('Ping');

    NotificationFacade::send($off, new TestPingNotification);
    NotificationFacade::send($on, new TestPingNotification);

    // Database delivery is unconditional; mail routing is decided by via().
    expect($off->notifications()->count())->toBe(1)
        ->and($on->notifications()->count())->toBe(1);
});

test('queued notifications dispatch through the queue', function () {
    Queue::fake();

    $user = User::factory()->create();

    NotificationFacade::send($user, new TestPingNotification);

    Queue::assertPushed(SendQueuedNotifications::class);
});

test('sendOnce deduplicates retries for the same key', function () {
    $user = User::factory()->create();

    // Simulate a retried sender delivering the same logical event twice.
    Notifications::sendOnce($user, 'article:42:submitted', new TestPingNotification('article:42:submitted'));
    Notifications::sendOnce($user, 'article:42:submitted', new TestPingNotification('article:42:submitted'));

    expect($user->notifications()->count())->toBe(1);
});

test('sendOnce allows distinct keys and re-notifies after read', function () {
    $user = User::factory()->create();

    Notifications::sendOnce($user, 'key-a', new TestPingNotification('key-a'));
    Notifications::sendOnce($user, 'key-b', new TestPingNotification('key-b'));

    expect($user->notifications()->count())->toBe(2);

    $user->unreadNotifications->markAsRead();

    Notifications::sendOnce($user, 'key-a', new TestPingNotification('key-a'));

    expect($user->notifications()->count())->toBe(3);
});

test('sendOnce rejects notifications missing the contract key', function () {
    $user = User::factory()->create();

    // No dedupe_key in data: silent no-dedup would be worse than loud failure.
    expect(fn () => Notifications::sendOnce($user, 'key', new TestPingNotification))
        ->toThrow(InvalidArgumentException::class);

    expect($user->notifications()->count())->toBe(0);
});

test('sendOnce scopes keys per user', function () {
    $one = User::factory()->create();
    $two = User::factory()->create();

    Notifications::sendOnce($one, 'shared', new TestPingNotification('shared'));
    Notifications::sendOnce($two, 'shared', new TestPingNotification('shared'));

    expect($one->notifications()->count())->toBe(1)
        ->and($two->notifications()->count())->toBe(1);
});
