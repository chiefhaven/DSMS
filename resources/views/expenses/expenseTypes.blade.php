@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Expense types</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">

            @if(Session::has('message'))
            <div class="alert alert-info">
              {{Session::get('message')}}
            </div>
          @endif

          @role(['superAdmin', 'admin'])
            <div class="">
                <a class="btn btn-primary rounded-pill px-4" href="/add-expense-type" data-bs-target="#modal-block-vcenter">
                    <i class="fa fa-file-plus"></i>&nbsp; Add expense type
                </a>
            </div>
            @endcan
        </nav>
      </div>
    </div>
  </div>

    <div class="content content-full" id="expenseTypes">
        <div class="block block-rounded block-bordered">
            <div class="block-content">
                <!-- Loading spinner -->
                <div v-if="loadingData" class="d-flex flex-column justify-content-center align-items-center" style="height: 300px;">
                <span class="spinner-border text-primary"></span>
                <p class="mt-3">Loading expense types...</p>
                </div>

                <!-- DataTable -->
                <div v-show="!loadingData" class="table-responsive">
                    <table id="expenseTypesTable" class="table table-bordered table-striped table-vcenter">
                        <thead class="thead-dark">
                            <tr>
                                <th class="text-center" style="min-width: 7em;">Actions</th>
                                <th style="min-width: 12rem;">Type</th>
                                <th style="min-width: 10rem;">Options</th>
                                <th class="text-center" style="min-width: 7rem;">Status</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script setup>

        const expenseTypes = createApp({
          setup() {
            const showExpenseTypeModal = ref(false);
            const loadingData = ref(false);
            const status = ref('all');

            onMounted(() => {
              nextTick(() => {
                setTimeout(() => {
                  getExpenses();
                }, 100);
              });
            });

            const reloadTable = (val) => {
              status.value = val;
              if ($.fn.DataTable.isDataTable('#expenseTypesTable')) {
                $('#expenseTypesTable').DataTable().ajax.reload();
              }
            };

            const getExpenses = () => {
              NProgress.start();
              loadingData.value = true;

              // Destroy old DataTable if exists
              if ($.fn.DataTable.isDataTable('#expenseTypesTable')) {
                $('#expenseTypesTable').DataTable().destroy();
              }

              $('#expenseTypesTable').DataTable({
                serverSide: true,
                processing: true,
                scrollCollapse: true,
                scrollX: true,
                ajax: async function (data, callback) {
                  try {
                    const csrfToken = $('meta[name="csrf-token"]').attr('content');
                    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

                    const response = await axios.get('/api/expense-types', {
                      params: { ...data },
                      withCredentials: true,
                      headers: {
                        'X-CSRF-TOKEN': csrfToken,
                      },
                    });

                    callback(response.data);
                    console.log(response.data);
                  } catch (error) {
                    let errorMessage = 'An error occurred while fetching data. Please try again later.';
                    if (error.response?.data?.error) {
                      errorMessage = error.response.data.error;
                    } else if (error.response?.data) {
                      errorMessage = error.response.data;
                    }

                    if ([401, 403, 409].includes(error.response?.status)) {
                      showError('Session expired, reloading...');
                      setTimeout(() => window.location.reload(), 1500);
                    } else {
                      showError('Something went wrong');
                      console.error(error);
                    }
                  } finally {
                    loadingData.value = false;
                    NProgress.done();
                  }
                },
                columns: [
                  { data: 'actions', className: 'text-center', orderable: false },
                  { data: 'type' },
                  { data: 'options' },
                  { data: 'status' },
                ],
                drawCallback: function () {
                  $('.delete-confirm').on('click', function (e) {
                    e.preventDefault();
                    var form = $(this).closest('form');
                    Swal.fire({
                      title: 'Delete expense',
                      text: 'Do you want to delete this expense type?',
                      icon: 'warning',
                      showCancelButton: true,
                      confirmButtonColor: '#d33',
                      cancelButtonColor: '#3085d6',
                      confirmButtonText: 'Delete!',
                      cancelButtonText: 'Cancel',
                    }).then((result) => {
                      if (result.isConfirmed) {
                        form.submit();
                        $('#expenseTypesTable').DataTable().ajax.reload();
                      }
                    });
                  });
                },
              });
            };

            const showError = (message, detail, { confirmText = 'OK', icon = 'error' } = {}) => {
              const baseOptions = {
                icon,
                title: message,
                text: detail,
                confirmButtonText: confirmText,
                didOpen: (toast) => {
                  toast.addEventListener('mouseenter', Swal.stopTimer);
                  toast.addEventListener('mouseleave', Swal.resumeTimer);
                },
              };

              const cleanOptions = Object.fromEntries(
                Object.entries(baseOptions).filter(([_, v]) => v !== undefined)
              );

              return Swal.fire(cleanOptions);
            };

            const showAlert = (message = '', detail = '', { icon = 'info' } = {}) => {
              const baseOptions = {
                icon,
                toast: true,
                timer: 3000,
                timerProgressBar: true,
                position: 'top-end',
                showConfirmButton: false,
                didOpen: (toast) => {
                  toast.addEventListener('mouseenter', Swal.stopTimer);
                  toast.addEventListener('mouseleave', Swal.resumeTimer);
                },
              };

              if (message) baseOptions.title = message;
              if (detail) baseOptions.text = detail;

              return Swal.fire(baseOptions);
            };

            return {
              reloadTable,
              loadingData,
            };
          },
        });

        expenseTypes.mount('#expenseTypes');
    </script>


<!-- END Hero -->


@endsection
