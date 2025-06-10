@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Payments</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">

            @if(Session::has('message'))
            <div class="alert alert-info">
              {{Session::get('message')}}
            </div>
          @endif

          @role(['superAdmin', 'admin'])
            <div class="">
                {{--  <a class="btn btn-primary" href="/addpayment" data-bs-target="#modal-block-vcenter">
                    <i class="fa fa-file-invoice-dollar"></i>&nbsp; Add payment
                </a>  --}}
            </div>
            @endcan
        </nav>
      </div>
    </div>
  </div>

    <div class="content content-full" id="payments">
        <div class="block block-rounded block-bordered">
            <div class="block-content">
                <!-- Loading spinner -->
                <div v-if="loadingData" class="d-flex flex-column justify-content-center align-items-center" style="height: 300px;">
                <span class="spinner-border text-primary"></span>
                <p class="mt-3">Loading payments...</p>
                </div>

                <!-- DataTable -->
                <div v-show="!loadingData" class="table-responsive">
                <table id="paymentsTable" class="table table-bordered table-striped table-vcenter">
                    <thead class="thead-dark">
                    <tr>
                        <th class="text-center" style="min-width: 100px;">Actions</th>
                        <th class="" style="min-width: 10em">Reference number</th>
                        <th style="min-width: 10em">Student</th>
                        <th style="min-width: 10em;">Payment Method</th>
                        <th style="min-width: 10em;">Amount</th>
                        <th style="min-width: 10em;">Entered By</th>
                        <th style="min-width: 10em;">Date</th>
                        <th style="min-width: 10em;">Payment Proof</th>
                    </tr>
                    </thead>
                </table>
                </div>
            </div>
        </div>
    </div>
    <script setup>
        const { createApp, ref, reactive, onMounted, nextTick } = Vue;

        const payments = createApp({
          setup() {
            const loadingData = ref(false);

            onMounted(() => {
              nextTick(() => {
                setTimeout(() => {
                  getPayments();
                }, 100);
              });
            });

            const reloadTable = (val) => {
              status.value = val;
              if ($.fn.DataTable.isDataTable('#paymentsTable')) {
                $('#paymentsTable').DataTable().ajax.reload();
              }
            };

            const getPayments = () => {
              NProgress.start();
              loadingData.value = true;

              // Destroy old DataTable instance if exists
              if ($.fn.DataTable.isDataTable('#paymentsTable')) {
                $('#paymentsTable').DataTable().destroy();
              }

              $('#paymentsTable').DataTable({
                serverSide: true,
                processing: true,
                scrollCollapse: true,
                scrollX: true,
                ajax: async function (data, callback) {
                  try {
                    const csrfToken = $('meta[name="csrf-token"]').attr('content');
                    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

                    const response = await axios.get('/api/payments', {
                      params: { ...data, status: status.value },
                      withCredentials: true,
                      headers: {
                        'X-CSRF-TOKEN': csrfToken,
                      },
                    });

                    callback(response.data);
                    console.log(response.data)
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
                    {
                        data: 'actions',
                        className: 'text-center',
                        orderable: false,
                        width: '100px'
                    },
                    {
                        data: 'transaction_id',
                        width: 'auto'
                    },
                    {
                        data: 'student',
                        width: 'auto',
                        className: 'text-title',
                    },
                    {
                        data: 'payment_method',
                        width: '20%'
                    },
                    {
                        data: 'amount',
                        className: 'text-right',
                        width: '15%'
                    },
                    {
                        data: 'entered_by',
                        width: '15%'
                    },
                    {
                        data: 'date',
                        width: '15%'
                    },
                    {
                        data: 'payment_proof',
                        width: '15%'
                    }
                ],
                drawCallback: function () {
                  $('.delete-confirm').on('click', function (e) {
                    e.preventDefault();
                    var form = $(this).closest('form');
                    Swal.fire({
                      title: 'Delete payment',
                      text: 'Do you want to delete this payment?',
                      icon: 'warning',
                      showCancelButton: true,
                      confirmButtonColor: '#d33',
                      cancelButtonColor: '#3085d6',
                      confirmButtonText: 'Delete!',
                      cancelButtonText: 'Cancel',
                    }).then((result) => {
                      if (result.isConfirmed) {
                        form.submit();
                        $('#paymentsTable').DataTable().ajax.reload();
                      }
                    });
                  });
                },
              });
            };

            const showError = (
              message,
              detail,
              { confirmText = 'OK', icon = 'error' } = {}
            ) => {
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

            const showAlert = (
              message = '',
              detail = '',
              { icon = 'info' } = {}
            ) => {
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

        payments.mount('#payments');
    </script>

<!-- END Hero -->


@endsection
