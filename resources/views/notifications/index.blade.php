<x-layouts::app :title="__('Notifications')">
    <x-ui.page-header title="Notifications" sub="Your editorial alerts. Email delivery is optional and off by default." />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-2">
        <p class="text-sm text-zinc-500">
            @if ($unreadCount > 0)
                {{ trans_choice('{1} :count unread notification|[2,*] :count unread notifications', $unreadCount, ['count' => $unreadCount]) }}
            @else
                {{ __('All caught up.') }}
            @endif
        </p>
        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <flux:button type="submit" variant="ghost">{{ __('Mark all read') }}</flux:button>
            </form>
            <form method="POST" action="{{ route('notifications.preferences') }}" class="flex items-center gap-2">
                @csrf
                <flux:switch name="email_notifications" label="Email me" :checked="$emailNotifications" />
                <flux:button type="submit" variant="ghost">{{ __('Save') }}</flux:button>
            </form>
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Notification</flux:table.column>
            <flux:table.column>Received</flux:table.column>
            <flux:table.column>Status</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($notifications as $notification)
                <flux:table.row :key="$notification->getKey()">
                    <flux:table.cell>{{ $notification->data['message'] ?? $notification->type }}</flux:table.cell>
                    <flux:table.cell>{{ $notification->created_at?->diffForHumans() }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($notification->read_at === null)
                            <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                @csrf
                                <flux:button type="submit" variant="ghost" size="sm">{{ __('Mark read') }}</flux:button>
                            </form>
                        @else
                            <flux:badge>Read</flux:badge>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3">
                        <x-ui.empty-state title="No notifications" body="Editorial events will appear here." />
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">{{ $notifications->links() }}</div>
</x-layouts::app>
