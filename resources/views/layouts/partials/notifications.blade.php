<div class="dropdown d-inline-block">
    @php
        $notifications = Auth::user()->notifications;
        $unreadNotificationsCount = Auth::user()->unreadNotifications->count();
    @endphp
    <button type="button" class="btn btn-alt-secondary position-relative" id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <!-- Unread notifications count -->
        @if($unreadNotificationsCount > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 12px; padding: 0.3em 0.6em;">
                {{ $unreadNotificationsCount }}
            </span>
        @endif
        <!-- Bell Icon -->
        <i class="fa fa-fw fa-bell"></i>
    </button>
    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0"
     aria-labelledby="page-header-notifications-dropdown"
     style="max-height: 400px; overflow-y: auto;">
    <div class="bg-primary fw-semibold text-white text-center p-3">
        Notifications
    </div>
    <div class="bg-light">
        @foreach ($notifications as $notification)
            @if(isset($notification->data['url']))
                <form action="{{ route('notifications.read', $notification->id) }}" method="POST" class="notification-form">
                    @csrf
                    @method('PATCH')
                    <a href="#" class="text-reset" onclick="this.closest('form').submit(); return false;">
                        <p class="p-3 m-2" style="background-color: {{ $notification->read() ? '' : '#3de6ff' }}; color: {{ $notification->unread() ? 'black' : '#303030' }}">
                            <strong>{{ $notification->data['title'] ?? 'No Title' }}</strong><br>
                            {{ $notification->data['body'] ?? 'No Body' }}
                            <br>
                            <small>{{ $notification->created_at->diffForHumans() }}</small>
                        </p>
                    </a>
                </form>
            @endif
        @endforeach

        @if($notifications->isEmpty())
            <p class="text-center p-3">No notifications available.</p>
        @endif
    </div>
</div>

</div>
