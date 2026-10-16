<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationsController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->latest()->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
            'emailNotifications' => (bool) $request->user()->email_notifications,
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        // Unchecked switches submit nothing; boolean() maps absence to false.
        $request->user()->forceFill(['email_notifications' => $request->boolean('email_notifications')])->save();

        return back()->with('toast', __('Notification preferences saved.'));
    }

    public function markRead(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        abort_unless((string) $notification->notifiable_id === (string) $request->user()->getKey(), 403);

        $notification->markAsRead();

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
