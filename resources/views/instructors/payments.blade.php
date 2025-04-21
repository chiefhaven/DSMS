@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Bonus payments</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb"></nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
          @if(Session::has('message'))
            <div class="alert alert-success">
              {{Session::get('message')}}
            </div>
          @endif

          @if ($errors->any())
              <div class="alert alert-danger">
                  <ul>
                      @foreach ($errors->all() as $error)
                          <li>{{ $error }}</li>
                      @endforeach
                  </ul>
              </div>
          @endif
        <div class="block block-rounded" id="payments">
            <div class="block-content">
                <table id="paymentsTable" class="table table-bordered table-striped table-vcenter table-responsive">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="min-width: 100px;">Actions</th>
                            <th style="min-width: 15rem;">Instructor</th>
                            <th style="min-width: 15rem;">Payment Month</th>
                            <th style="min-width: 10rem;">Total attendance</th>
                            <th style="min-width: 10rem;">Pay/Attendance</th>
                            <th style="min-width: 10rem;">Total Payment</th>
                            <th style="min-width: 10rem;">Payment Date</th>
                            <th style="min-width: 10rem;">Status</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
  </div>

    <script setup>
        const { createApp, ref, reactive, onMounted, nextTick } = Vue

        const payments = createApp({
            setup() {

                onMounted(() => {
                    nextTick(() => {
                      setTimeout(() => {
                        getPayments();
                      }, 100);
                    });
                });

                const getPayments = () => {
                    NProgress.start();
                    const table = $('#paymentsTable').DataTable();
                    if ($.fn.DataTable.isDataTable('#paymentsTable')) {
                        table.destroy();
                    }
                    $('#paymentsTable').DataTable({
                      serverSide: true,
                      processing: true,
                      scrollCollapse: true,
                      scrollX: true,
                      ajax: async function(data, callback, settings) {
                        try {
                            const csrfToken = $('meta[name="csrf-token"]').attr('content');
                            axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
                            const response = await axios.get('/fetchPayments', {
                                params: { ...data, status: status.value },
                                withCredentials: true,
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                }
                            });

                            callback(response.data);

                        } catch (error) {
                            let errorMessage = 'An error occurred while fetching data. Please try again later.';

                            if (error.response?.data?.error) {
                                errorMessage = error.response.data.error;
                            } else if (error.response?.data) {
                                errorMessage = error.response.data;
                            }

                            if ([401, 403, 409].includes(error.response?.status)) {
                                showAlert('Session expired', 'Session expired, reloading...', {
                                    toast: false,
                                    icon: 'error',
                                    confirmText: 'Ok'
                                });
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                showAlert('', 'Failed to load data...', {
                                    toast: false,
                                    icon: 'error',
                                    confirmButtonText: 'Ok'
                                });
                            }
                        } finally{
                            NProgress.done();
                        }
                    },
                      columns: [
                        { data: 'actions', className: 'text-center', orderable: false },
                        { data: 'instructor' },
                        { data: 'payment_month', className: 'text-right' },
                        { data: 'attendances', className: 'text-wrap' },
                        { data: 'per_attendance', className: 'text-right' },
                        { data: 'total', className: 'text-right' },
                        { data: 'payment_date', className: 'text-right' },
                        { data: 'status', className: 'text-wrap' }
                      ],
                      drawCallback: function () {
                        // Bind change status buttons (dropdown)
                        $('.change-status-btn').on('click', function () {
                            const id = $(this).data('id');
                            const status = $(this).data('status');
                            const fname = $(this).data('fname');
                            const mname = $(this).data('mname');
                            const sname = $(this).data('sname');

                            const fullName = `${fname} ${mname ?? ''} ${sname}`.trim();

                            openStatusChangeModal(id, status, fullName);
                        });


                        $(document).on('click', '.status-span', function () {
                            const id = $(this).data('id');
                            const status = $(this).data('status');
                            const fname = $(this).data('fname');
                            const mname = $(this).data('mname');
                            const sname = $(this).data('sname');

                            const fullName = `${fname} ${mname || ''} ${sname}`.trim();

                            openStatusChangeModal(id, status, fullName);
                        });

                        $('.delete-confirm').on('click', function (e) {
                          e.preventDefault();
                          var form = $(this).closest('form');
                          Swal.fire({
                            title: 'Delete Student',
                            text: 'Do you want to delete this payment?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'Delete!',
                            cancelButtonText: 'Cancel'
                          }).then((result) => {
                            if (result.isConfirmed) {
                              form.submit();
                              $('#paymentsTable').DataTable().ajax.reload();
                            }
                          });
                        });
                      }
                    });
                };

                const showAlert = (
                    message = '', // Optional title
                    detail = '',  // Optional detail text
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
                        }
                    };

                    // Only include title and text if they’re not empty
                    if (message) baseOptions.title = message;
                    if (detail) baseOptions.text = detail;

                    return Swal.fire(baseOptions);
                };


                return {
                    payments
                }
            }})

        payments.mount('#payments');
    </script>
  <!-- END Hero -->

@endsection
