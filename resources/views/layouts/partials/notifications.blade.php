<div class="dropdown d-inline-block">
    @php
        $user = Auth::user();
        $notifications = $user?->notifications->take(10) ?? collect();
        $unreadNotificationsCount = $user?->unreadNotifications?->count() ?? 0;
    @endphp

    <!-- Notification Bell Button -->
    <button type="button" class="btn btn-alt-secondary position-relative"
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
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
         aria-labelledby="page-header-notifications-dropdown"
         style="max-height: 400px; overflow-y: auto;" role="menu" aria-label="Notifications">

        <!-- Header -->
        <div class="bg-primary fw-semibold text-white text-center p-3">
            Notifications
        </div>
        @if($unreadNotificationsCount > 0)
            <div class="fw-semibold text-center p-3">
                <form action="{{ route('notifications.markAllRead') }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-link text-decoration-none p-0 m-0">Mark all as read</button>
                </form>
            </div>
        @endif
        <!-- Notification List -->
        <div class="bg-light">
            @forelse ($notifications as $notification)
                @if(isset($notification->data['url']))
                    <form action="{{ route('notifications.read', $notification->id) }}" method="POST" class="notification-form">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="redirect_url" value="{{ $notification->data['url'] }}">

                        <a href="#" class="text-reset" onclick="event.preventDefault(); this.closest('form').submit();">
                            <p class="p-3 m-2 rounded"
                            style="background-color: {{ $notification->read() ? '#f8f9fa' : '#3de6ff' }};
                                    color: {{ $notification->unread() ? 'black' : '#303030' }};
                                    border-left: 5px solid {{ $notification->unread() ? '#007bff' : '#ccc' }};">
                                <strong>{{ $notification->data['title'] ?? 'No Title' }}</strong><br>
                                {{ $notification->data['body'] ?? 'No Body' }}<br>
                                <small>{{ $notification->created_at->diffForHumans() }}</small>
                            </p>
                        </a>
                    </form>
                @endif
            @empty
                <p class="text-center p-3">No notifications available.</p>
            @endforelse

            @if($notifications->isNotEmpty())
                <div class="text-center p-2">
                    <a href="{{ route('notifications.index') }}" class="btn btn-link text-decoration-none">
                        All notifications
                    </a>
                </div>
            @endif
        </div>

    </div>
</div>
