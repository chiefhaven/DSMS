@extends('layouts.backend')

@section('content')
    <div class="bg-body-light">
      <div class="content content-full">
        <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
          <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Notifications</h1>
          <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3">
            <form action="{{ route('notifications.markAllRead', [], false) }}" method="POST">
              @csrf
              @method('PATCH')
              <button type="submit" class="btn btn-alt-primary notification-page-mark-all">
                <i class="fa fa-check-double me-2" aria-hidden="true"></i>
                Mark all as read
              </button>
            </form>
          </nav>
        </div>
      </div>
    </div>

    <div class="content content-full notification-page">
      <div class="block block-rounded block-bordered notification-center mb-0" id="groupNotifications"
           data-load-url="{{ route('notifications.loadNotications', [], false) }}"
           data-read-url="{{ route('notifications.read', ['notificationId' => '__notification__'], false) }}">
        <div class="block-header block-header-default notification-toolbar">
          <h3 class="block-title">Inbox</h3>
          <div class="btn-group btn-group-sm" role="group" aria-label="Notification filters">
            <button type="button"
                    class="btn notification-filter"
                    :class="activeFilter === 'all' ? 'btn-primary' : 'btn-alt-secondary'"
                    :aria-pressed="activeFilter === 'all'"
                    @click="activeFilter = 'all'">
              All <span class="badge rounded-pill ms-1"
                        :class="activeFilter === 'all' ? 'bg-white text-primary' : 'bg-body-light text-muted'">
                @{{ notifications.length }}
              </span>
            </button>
            <button type="button"
                    class="btn notification-filter"
                    :class="activeFilter === 'unread' ? 'btn-primary' : 'btn-alt-secondary'"
                    :aria-pressed="activeFilter === 'unread'"
                    @click="activeFilter = 'unread'">
              Unread <span class="badge rounded-pill ms-1"
                           :class="activeFilter === 'unread' ? 'bg-white text-primary' : 'bg-body-light text-muted'">
                @{{ unreadCount }}
              </span>
            </button>
          </div>
        </div>

        <div class="block-content p-0 notification-list-wrap">
          <div v-if="isLoading" class="notifications-list" aria-busy="true" aria-label="Loading notifications">
            <article v-for="n in 5" :key="n" class="list-group-item notification-card notification-skeleton-card" aria-hidden="true">
              <div class="notification-skeleton notification-skeleton-icon"></div>
              <div class="notification-card-copy">
                <div class="notification-skeleton notification-skeleton-title"></div>
                <div class="notification-skeleton notification-skeleton-body"></div>
                <div class="notification-skeleton notification-skeleton-meta"></div>
              </div>
            </article>
          </div>

          <div v-else>
            <div v-if="filteredNotifications.length > 0">
              <div class="notifications-list" @scroll="loadMoreNotifications">
                <article v-for="notification in filteredNotifications" :key="notification.id"
                         class="list-group-item list-group-item-action notification-card"
                         :class="{ 'is-unread': !notification.read_at }"
                         @click="markAsRead(notification)">
                  <div class="notification-card-icon bg-body-light text-primary">
                    <i class="fa fa-bell" aria-hidden="true"></i>
                  </div>
                  <div class="notification-card-copy">
                    <div class="notification-card-title-row">
                      <strong>@{{ notification.data.title || 'Notification' }}</strong>
                      <span v-if="!notification.read_at" class="badge bg-primary notification-card-new">New</span>
                    </div>
                    <p>@{{ notification.data.body || 'You have a new update.' }}</p>
                    <small>@{{ timeAgo(notification.created_at) }}</small>
                  </div>
                  <button type="button" class="btn btn-sm btn-alt-secondary notification-card-open" aria-label="Open notification">
                    <i class="fa fa-chevron-right" aria-hidden="true"></i>
                  </button>
                </article>

                <article v-if="isLoadingMore" v-for="n in 2" :key="`loading-${n}`"
                         class="list-group-item notification-card notification-skeleton-card" aria-hidden="true">
                  <div class="notification-skeleton notification-skeleton-icon"></div>
                  <div class="notification-card-copy">
                    <div class="notification-skeleton notification-skeleton-title"></div>
                    <div class="notification-skeleton notification-skeleton-body"></div>
                    <div class="notification-skeleton notification-skeleton-meta"></div>
                  </div>
                </article>
                <div v-else-if="allLoaded && notifications.length > 0" class="notification-end">
                  You are all caught up
                </div>
              </div>
            </div>

            <div v-else class="notification-empty-state">
              <div class="notification-empty-icon bg-body-light text-primary rounded-circle">
                <i class="fa fa-bell-slash" aria-hidden="true"></i>
              </div>
              <h2 class="h5 fw-semibold">@{{ activeFilter === 'unread' ? 'No unread notifications' : 'No notifications yet' }}</h2>
              <p class="text-muted">@{{ activeFilter === 'unread' ? 'You have reviewed all your updates.' : 'New updates will appear here.' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>

        const sendGroupNotification = createApp({
          setup() {
            const root = document.getElementById('groupNotifications');
            const loadUrl = root.dataset.loadUrl;
            const readUrl = root.dataset.readUrl;
            const notifications = ref([]);
            const isLoading = ref(false);
            const page = ref(1);
            const allLoaded = ref(false);
            const isLoadingMore = ref(false);
            const activeFilter = ref('all');

            const loadNotifications = async (loadPage = 1) => {
                if(loadPage === 1) {
                  isLoading.value = true;
                } else {
                  isLoadingMore.value = true;
                }
                try {
                  const response = await axios.get(`${loadUrl}?page=${loadPage}`);
                  if(loadPage === 1){
                    notifications.value = response.data;
                  } else {
                    notifications.value.push(...response.data);
                  }
                  allLoaded.value = response.data.length < 10;
                  page.value = loadPage;
                } catch (error) {
                  showError('Failed to load notifications', error.message);
                } finally {
                  isLoading.value = false;
                  isLoadingMore.value = false;
                }
              };

            onMounted(() => {
              nextTick(() => loadNotifications());
            });

            const markAsRead = async (notification) => {
              NProgress.start();
              try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                const notificationReadUrl = readUrl.replace('__notification__', encodeURIComponent(notification.id));
                await axios.patch(notificationReadUrl, { api: true }, {
                  headers: { 'X-CSRF-TOKEN': csrfToken }
                });

                window.location.href = notification.data.url || '/';

              } catch (error) {
                console.log(error);
                showError('Error', 'Something wrong happened.');
              } finally {
                NProgress.done();
              }
            };

            const timeAgo = (date) => {
              return dayjs(date).fromNow();
            };

            const showError = (
              message,
              detail,
              { confirmText = 'OK', icon = 'error' } = {}
            ) => {
              return Swal.fire({
                icon,
                title: message,
                text: detail,
                confirmButtonText: confirmText,
              });
            };

            const loadMoreNotifications = (event) => {
                const container = event.target;
                if (
                  container.scrollTop + container.clientHeight >= container.scrollHeight - 120 &&
                  !isLoadingMore.value &&
                  !allLoaded.value
                ) {
                  loadNotifications(page.value + 1);
                }
            };

            const unreadCount = computed(() =>
              notifications.value.filter(notification => !notification.read_at).length
            );
            const filteredNotifications = computed(() =>
              activeFilter.value === 'unread'
                ? notifications.value.filter(notification => !notification.read_at)
                : notifications.value
            );

            return {
              notifications,
              activeFilter,
              unreadCount,
              filteredNotifications,
              timeAgo,
              markAsRead,
              isLoading,
              loadMoreNotifications,
              isLoadingMore,
              allLoaded
            };
          }
        });

        sendGroupNotification.mount('#groupNotifications');
    </script>
<!-- END Hero -->
@endsection
