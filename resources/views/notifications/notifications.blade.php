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
            <div v-if="notifications.length > 0">
              <div
                v-for="notification in notifications"
                :key="notification.id"
              >
                <a
                  href="#"
                  class="text-reset"
                  @click.prevent="markAsRead(notification)"
                >
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

            </div>

            <p v-else class="text-center p-3">No notifications available.</p>
          </div>
    </div>

    <script>
        const { createApp, ref, onMounted, nextTick } = Vue;

        const notificationsApp = createApp({
          setup() {
            const notifications = ref([]);

            onMounted(() => {
              nextTick(() => {
                setTimeout(() => {
                  loadNotifications();
                }, 100);
              });
            });

            const loadNotifications = async () => {
              NProgress.start();
              try {
                const response = await axios.get('/load-notifications');
                notifications.value = response.data;
                console.log(notifications.value);
              } catch (error) {
                console.error('Error fetching notifications:', error);
                showError('Failed to load notifications', error.message);
              } finally {
                NProgress.done();
              }
            };

            const markAsRead = async (notification) => {
                NProgress.start();
                try {
                  const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                  await axios.patch(`/notifications/${notification.id}/read`, {
                    redirect_url: notification.data.url
                  }, {
                    headers: {
                      'X-CSRF-TOKEN': csrfToken
                    }
                  });

                  // Optionally mark as read locally
                  notification.read_at = new Date().toISOString();
                  window.location.href = notification.data.url;

                } catch (error) {
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
              {
                confirmText = 'OK',
                icon = 'error',
              } = {}
            ) => {
              return Swal.fire({
                icon,
                title: message,
                text: detail,
                confirmButtonText: confirmText,
              });
            };

            const showAlert = (
              message = '',
              detail = '',
              { icon = 'info' } = {}
            ) => {
              return Swal.fire({
                icon,
                toast: true,
                timer: 3000,
                timerProgressBar: true,
                position: 'top-end',
                showConfirmButton: false,
                title: message || undefined,
                text: detail || undefined,
                didOpen: (toast) => {
                  toast.addEventListener('mouseenter', Swal.stopTimer);
                  toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
              });
            };

            return {
              notifications,
              timeAgo,
              markAsRead,
            };
          }
        });

        notificationsApp.mount('#notifications');
    </script>


<!-- END Hero -->
@endsection
