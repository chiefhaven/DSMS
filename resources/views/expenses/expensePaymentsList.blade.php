@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Expense payments</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">

            @if(Session::has('message'))
            <div class="alert alert-info">
              {{Session::get('message')}}
            </div>
          @endif
        </nav>
      </div>
    </div>
  </div>

    <div class="content content-full" id="expensePaymentList">
        <div class="block block-rounded block-bordered">
            <div class="block-content">
                <!-- Loading spinner -->
                <div v-if="loadingData" class="d-flex flex-column justify-content-center align-items-center" style="height: 300px;">
                <span class="spinner-border text-primary"></span>
                <p class="mt-3">Loading expense payments...</p>
                </div>

                <!-- DataTable -->
                <div v-show="!loadingData" class="table-responsive">
                <table id="expensePaymentsTable" class="table table-bordered table-striped table-vcenter">
                    <thead class="thead-dark">
                    <tr>
                        <th class="text-center" style="min-width: 7em;">Actions</th>
                        <th style="min-width: 12rem;">Student</th>
                        <th style="min-width: 12rem;">Group</th>
                        <th class="text-center" style="min-width: 7rem;">Expense type</th>
                        <th class="text-center" style="min-width: 7rem;">Amount</th>
                        <th class="text-center" style="min-width: 10rem;">Payment method</th>
                        <th style="min-width: 10rem;">Paid by</th>
                        <th style="min-width: 10rem;">Date paid</th>
                    </tr>
                    </thead>
                </table>
                </div>
            </div>
        </div>
    </div>
    <script setup>

        const expensePaymentList = createApp({
          setup() {
            const loadingData = ref(false);
            const status = ref('all');

            const reloadTable = (val) => {
              status.value = val;
              if ($.fn.DataTable.isDataTable('#expensePaymentsTable')) {
                $('#expensePaymentsTable').DataTable().ajax.reload();
              }
            };

            const getExpensePayments = () => {
              NProgress.start();
              loadingData.value = true;

              if ($.fn.DataTable.isDataTable('#expensePaymentsTable')) {
                $('#expensePaymentsTable').DataTable().destroy();
              }

              $('#expensePaymentsTable').DataTable({
                serverSide: true,
                processing: true,
                scrollCollapse: true,
                scrollX: true,
                ajax: async function (data, callback) {
                  try {
                    const csrfToken = $('meta[name="csrf-token"]').attr('content');
                    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

                    const response = await axios.get('/api/expense-payments', {
                      params: { ...data },
                      withCredentials: true,
                      headers: { 'X-CSRF-TOKEN': csrfToken },
                    });

                    callback(response.data);
                  } catch (error) {
                    let errorMessage = 'An error occurred while fetching data.';
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
                  { data: 'student' },
                  { data: 'group' },
                  { data: 'expense_type' },
                  { data: 'amount', className: 'text-end' },
                  { data: 'payment_method' },
                  { data: 'paid_by', className: 'text-center' },
                  { data: 'date_paid', className: 'text-center' },
                ],
                drawCallback: function () {
                  $('.delete-confirm').on('click', function (e) {
                    e.preventDefault();
                    const form = $(this).closest('form');
                    Swal.fire({
                        title: 'Delete payment',
                        text: 'Do you want to delete this payment?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Delete!',
                        cancelButtonText: 'Cancel',
                        customClass: {
                          confirmButton: 'btn btn-danger rounded-pill px-4 me-2',
                          cancelButton: 'btn btn-outline-secondary rounded-pill px-4'
                        },
                        buttonsStyling: false
                      }).then((result) => {
                        if (result.isConfirmed) {
                            axios.post('/api/delete-payment/' + paymentId)
                            .then(() => {
                              showSuccess('Payment reversed successfully.');
                              $('#expensePaymentsTable').DataTable().ajax.reload();
                            })
                            .catch((error) => {
                              showError('Failed to delete payment.');
                              console.error(error);
                            });
                            $('#expensePaymentsTable').DataTable().ajax.reload();
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
              };
              return Swal.fire(baseOptions);
            };

            const showAlert = (message = '', detail = '', { icon = 'info' } = {}) => {
              const baseOptions = {
                icon,
                toast: true,
                timer: 3000,
                timerProgressBar: true,
                position: 'top-end',
                showConfirmButton: false,
              };
              if (message) baseOptions.title = message;
              if (detail) baseOptions.text = detail;
              return Swal.fire(baseOptions);
            };

            const showSuccess = (message) => {
              return showAlert(message, '', { icon: 'success' });
            };

            const deletePayment = (paymentId) => {
              Swal.fire({
                title: 'Delete Payment',
                text: 'Are you sure you want to delete this payment?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes',
                customClass: {
                    confirmButton: 'btn btn-danger rounded-pill px-4 me-2',
                    cancelButton: 'btn btn-outline-secondary rounded-pill px-4'
                  },
              }).then((result) => {
                if (result.isConfirmed) {
                    axios.post('/api/delete-payment/' + paymentId)
                    .then(() => {
                      showSuccess('Payment deleted successfully.');
                      $('#expensePaymentsTable').DataTable().ajax.reload();
                    })
                    .catch((error) => {
                      showError('Failed to delete payment.');
                      console.error(error);
                    });
                }
              });
            };

            onMounted(() => {
              nextTick(() => {
                getExpensePayments();
                window.deletePayment = deletePayment;
              });
            });

            return {
              reloadTable,
              loadingData,
              deletePayment,
            };
          },
        });

        expensePaymentList.mount('#expensePaymentList');
    </script>
<!-- END Hero -->
@endsection
