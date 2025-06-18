@extends('layouts.backend')

@section('content')
    <!-- Hero -->
    <div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Notifications</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">

            @if(Session::has('message'))
            <div class="alert alert-info">
                {{Session::get('message')}}
            </div>
            @endif

            @role(['superAdmin', 'admin'])
                <div class="dropdown d-inline-block">

                </div>
            @endcan
        </nav>
        </div>
    </div>
    </div>

    <div class="content content-full" id="notifications">
        <div class="bg-light p-4">
          <div v-if="isLoading" class="text-center py-7">
            <span class="spinner-border text-primary" role="status"></span>
            <p>Loading notifications</p>
          </div>

          <div v-else>
            <div v-if="notifications.length > 0">
              <!-- Scroll container -->
              <div
                class="notifications-list"
                style="max-height: 1000px; overflow-y: auto;"
                @scroll="loadMoreNotifications"
              >
                <div v-for="notification in notifications" :key="notification.id">
                  <a href="#" class="text-reset" @click.prevent="markAsRead(notification)">
                    <p
                      class="p-3 m-2 rounded"
                      :style="{
                        backgroundColor: notification.read_at ? '#f8f9fa' : '#3de6ff',
                        color: notification.read_at ? '#303030' : 'black',
                        borderLeft: `5px solid ${notification.read_at ? '#ccc' : '#007bff'}`,
                      }"
                    >
                      <strong>@{{ notification.data.title || 'No Title' }}</strong><br />
                      @{{ notification.data.body || 'No Body' }}<br />
                      <small>@{{ timeAgo(notification.created_at) }}</small>
                    </p>
                  </a>
                </div>

                <div v-if="isLoadingMore" class="text-center p-2">
                  <span class="spinner-border spinner-border-sm text-primary"></span> Loading more...
                </div>
              </div>
            </div>

            <p v-else class="text-center p-3 text-muted">
              <i class="fa fa-bell-slash"></i> No notifications available.
            </p>
          </div>
        </div>
      </div>

    <script>

        const notificationsApp = createApp({
          setup() {
            const notifications = ref([]);
            const isLoading = ref(false);
            const page = ref(1);
            const allLoaded = ref(false);
            const isLoadingMore = ref(false);

            const loadNotifications = async (loadPage = 1) => {
                if(loadPage === 1) {
                  isLoading.value = true;
                } else {
                  isLoadingMore.value = true;
                }
                try {
                  const response = await axios.get(`/load-notifications?page=${loadPage}`);
                  if(loadPage === 1){
                    notifications.value = response.data;
                  } else {
                    notifications.value.push(...response.data);
                  }
                  page.value = loadPage;
                } catch (error) {
                  showError('Failed to load notifications', error.message);
                } finally {
                  isLoading.value = false;
                  isLoadingMore.value = false;
                }
              };

            // Scroll handler to detect when near bottom
            const onScroll = () => {
              const container = document.getElementById('notifications');
              if (!container) return;

              const threshold = 150; // px from bottom to trigger load
              if (container.scrollTop + container.clientHeight >= container.scrollHeight - threshold) {
                loadNotifications();
              }
            };

            onMounted(() => {
              nextTick(() => {
                loadNotifications();

                const container = document.getElementById('notifications');
                if (container) {
                  container.addEventListener('scroll', onScroll);
                }
              });
            });

            onUnmounted(() => {
              const container = document.getElementById('notifications');
              if (container) {
                container.removeEventListener('scroll', onScroll);
              }
            });

            const markAsRead = async (notification) => {
              NProgress.start();
              try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                await axios.patch(`/notifications/${notification.id}/read`, { api: true }, {
                  headers: { 'X-CSRF-TOKEN': csrfToken }
                });

                window.location.href = notification.data.url;

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

            const loadMoreNotifications = () => {
                if(!isLoadingMore.value) {
                  loadNotifications(page.value + 1);
                }
            };

            return {
              notifications,
              timeAgo,
              markAsRead,
              isLoading,
              loadMoreNotifications,
              isLoadingMore
            };
          }
        });

        notificationsApp.mount('#notifications');
    </script>



<!-- END Hero -->
@endsection
