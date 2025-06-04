@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Expenses</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">

            @if(Session::has('message'))
            <div class="alert alert-info">
              {{Session::get('message')}}
            </div>
          @endif

          @role(['superAdmin', 'admin'])
            <div class="">
                <a class="btn btn-primary" href="/addexpense" data-bs-target="#modal-block-vcenter">
                    <i class="fa fa-file-invoice-dollar"></i>&nbsp; Add expense
                </a>
            </div>
            @endcan
        </nav>
      </div>
    </div>
  </div>

    <div class="content content-full" id="expenses">
        <div class="block block-rounded block-bordered">
            <div class="block-content">
                <div class="table-responsive">
                    <table id="expensesTable" class="table table-bordered table-striped table-vcenter">
                        <thead class="thead-dark">
                            <tr>
                                <th class="text-center" style="width: 100px;">Actions</th>
                                <th style="min-width: 12rem;">Group</th>
                                <th class="text-center" style="min-width: 7rem;">Students count</th>
                                <th class="text-center" style="min-width: 7rem;">Status</th>
                                <th style="min-width: 7rem;">Type</th>
                                <th style="min-width: 10rem;">Description</th>
                                <th style="min-width: 10rem;">Posted by</th>
                                <th style="min-width: 10rem;">Amount/student</th>
                                <th style="min-width: 10rem;">Approved by</th>
                                <th style="min-width: 10rem;">Date Approved</th>
                                <th style="min-width: 10rem;">Last edited</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script setup>
        const { createApp, ref, reactive, onMounted, nextTick } = Vue

        const expenses = createApp({
        setup() {

            const showStatusChangeModal = ref(false);

            onMounted(() => {
                nextTick(() => {
                  setTimeout(() => {
                    getExpenses();
                  }, 100);
                });
            });

            const reloadTable = (val) => {
                status.value = val
                if ($.fn.DataTable.isDataTable('#expensesTable')) {
                    $('#expensesTable').DataTable().ajax.reload();
                  }
            }

            const getExpenses = () => {
                NProgress.start();
                const table = $('#expensesTable').DataTable();
                if ($.fn.DataTable.isDataTable('#expensesTable')) {
                    table.destroy();
                }
                $('#expensesTable').DataTable({
                  serverSide: true,
                  processing: true,
                  scrollCollapse: true,
                  scrollX: true,
                  ajax: async function(data, callback, settings) {
                    try {
                        const csrfToken = $('meta[name="csrf-token"]').attr('content');
                        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
                        const response = await axios.get('/api/expenses', {
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
                            showError('Session expired, reloading...');
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            showError('Something wrong happened');
                            console.log (error.response)
                        }
                    } finally{
                        NProgress.done();
                    }
                },
                columns: [
                    { data: 'actions', className: 'text-center', orderable: false },
                    { data: 'group' },
                    { data: 'students' },
                    { data: 'status' },
                    { data: 'type' },
                    { data: 'description' },
                    { data: 'posted_by' },
                    { data: 'amount', className: 'text-right' },
                    { data: 'approved_by' },
                    { data: 'date_approved', className: 'text-center' },
                    { data: 'last_edited', className: 'text-center' }
                  ],
                  drawCallback: function () {

                    $('.delete-confirm').on('click', function (e) {
                      e.preventDefault();
                      var form = $(this).closest('form');
                      Swal.fire({
                        title: 'Delete expense',
                        text: 'Do you want to delete this expense?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Delete!',
                        cancelButtonText: 'Cancel'
                      }).then((result) => {
                        if (result.isConfirmed) {
                          form.submit();
                          $('#expensesTable').DataTable().ajax.reload();
                        }
                      });
                    });
                  }
                });
            };

            const showError = (
                message,
                detail,
                {
                    confirmText = 'OK',
                    icon = 'error',
                } = {}
                ) => {
                const baseOptions = {
                    icon,
                    title: message,
                    text: detail,
                    confirmButtonText: confirmText,
                    didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                    }
                };

                // Clean up undefined options
                const cleanOptions = Object.fromEntries(
                    Object.entries(baseOptions).filter(([_, v]) => v !== undefined)
                );

                return Swal.fire(cleanOptions);
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
                reloadTable,
            }

        }})

        expenses.mount('#expenses');
    </script>
<!-- END Hero -->


@endsection
