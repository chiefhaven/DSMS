<div class="dropdown d-inline-block notification-dropdown">
    @php
        $user = Auth::user();
        $notifications = $user?->notifications->take(10) ?? collect();
        $unreadNotificationsCount = $user?->unreadNotifications?->count() ?? 0;
    @endphp

    <!-- Notification Bell Button -->
    <button type="button" class="btn btn-alt-secondary position-relative notification-bell"
            id="page-header-notifications-dropdown"
            data-bs-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
            title="View notifications">

        <!-- Unread Count Badge -->
        @if($unreadNotificationsCount > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                  style="font-size: 12px; padding: 0.3em 0.6em;">
                {{ $unreadNotificationsCount }}
            </span>
        @endif

        <!-- Bell Icon -->
        <i class="fa fa-fw fa-bell"></i>
    </button>

    <!-- Dropdown Menu -->
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0 notification-menu"
         aria-labelledby="page-header-notifications-dropdown" role="menu" aria-label="Notifications">

        <!-- Header -->
        <div class="notification-menu-header">
            <div>
                <span class="notification-menu-eyebrow">Inbox</span>
                <h6 class="mb-0 fw-semibold">Notifications</h6>
            </div>
            @if($unreadNotificationsCount > 0)
                <form action="{{ route('notifications.markAllRead', [], false) }}" method="POST" class="mb-0">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="notification-mark-all" title="Mark all notifications as read">
                        <i class="fa fa-check-double" aria-hidden="true"></i>
                        <span>Mark all read</span>
                    </button>
                </form>
            @endif
        </div>
        <!-- Notification List -->
        <div class="notification-menu-list">
            @forelse ($notifications as $notification)
                @if(isset($notification->data['url']))
                    <form action="{{ route('notifications.read', ['notificationId' => $notification->id], false) }}" method="POST" class="notification-form">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="redirect_url" value="{{ $notification->data['url'] }}">

                        <a href="#" class="notification-menu-item {{ $notification->unread() ? 'is-unread' : '' }}"
                           onclick="event.preventDefault(); this.closest('form').submit();">
                            <div class="notification-icon">
                                <i class="fa fa-bell" aria-hidden="true"></i>
                            </div>
                            <div class="notification-menu-copy">
                                <strong>{{ $notification->data['title'] ?? 'Notification' }}</strong>
                                <p>{{ $notification->data['body'] ?? 'You have a new update.' }}</p>
                                <small>{{ $notification->created_at->diffForHumans() }}</small>
                            </div>
                            @if($notification->unread())
                                <span class="notification-unread-dot" aria-label="Unread"></span>
                            @endif
                        </a>
                    </form>
                @endif
            @empty
                <div class="notification-empty">
                    <i class="fa fa-bell-slash" aria-hidden="true"></i>
                    <p>No notifications available.</p>
                </div>
            @endforelse

            @if($notifications->isNotEmpty())
                <div class="notification-menu-footer">
                    <a href="{{ route('notifications.index') }}">
                        View all notifications
                        <i class="fa fa-arrow-right ms-1" aria-hidden="true"></i>
                    </a>
                </div>
            @endif
        </div>

    </div>
</div>
