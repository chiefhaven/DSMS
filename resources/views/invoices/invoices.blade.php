@extends('layouts.backend')

@section('content')
    <!-- Hero -->
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Invoices</h1>
            </div>
        </div>
    </div>

    <div class="content content-full">
        @if(Session::has('message'))
        <div class="alert alert-info">
            {{Session::get('message')}}
        </div>
        @endif
        <div class="block block-rounded block-bordered" id="invoices">
            <div class="block-content">
                <div class="table-responsive">
                    <table id="invoicesTable" class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                            <th class="text-center">Actions</th>
                            <th style="min-width: 15rem">Invoice No</th>
                            <th style="min-width: 15rem">Student</th>
                            <th style="min-width: 10rem">Course</th>
                            <th style="min-width: 10rem">Course Price</th>
                            <th style="min-width: 10rem">Discount</th>
                            <th style="min-width: 10rem">Total</th>
                            <th style="min-width: 10rem">Paid</th>
                            <th style="min-width: 10rem">Balance</th>
                            <th style="min-width: 10rem">Date created</th>
                            <th style="min-width: 10rem">Due</th>
                            <th style="min-width: 10rem">Updated at</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

<script type="text/javascript">
    $('.delete-confirm').on('click', function (e) {
        e.preventDefault();
        var form = $(this).parents('form');
        Swal.fire({
            title: 'Delete Invoice',
            text: 'This will un-enroll the student from the course, continue?',
            icon: 'error',
            confirmButtonText: 'Delete'
        }).then((result) => {
            if (result.isConfirmed)
                form.submit();
        });
    });

</script>

<!-- Vue app -->
<script setup>
    const { createApp, ref, onMounted } = Vue;

    const invoices = createApp({
      setup() {
        const status = ref('active');
        const showStatusChangeModal = ref(false);
        const studentId = ref(null);
        const studentName = ref('');
        const invoicestatus = ref('');
        const isSaving = ref(false);

        onMounted(() => {
          getinvoices();
        });

        const reloadTable = (val = status.value) => {
          status.value = val;
          if ($.fn.DataTable.isDataTable('#invoicesTable')) {
            $('#invoicesTable').DataTable().ajax.reload();
          }
        };

        const getinvoices = () => {
          NProgress.start();

          if ($.fn.DataTable.isDataTable('#invoicesTable')) {
            $('#invoicesTable').DataTable().destroy();
          }

          $('#invoicesTable').DataTable({
            serverSide: true,
            processing: true,
            scrollCollapse: true,
            scrollX: true,
            ajax: async function (data, callback) {
              try {
                const csrfToken = $('meta[name="csrf-token"]').attr('content');
                axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;

                const response = await axios.get('/api/fetchInvoices', {
                  params: { ...data, status: status.value },
                  withCredentials: true,
                  headers: {
                    'X-CSRF-TOKEN': csrfToken
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
                  showError('Error', errorMessage);
                  console.error(error);
                }
              } finally {
                NProgress.done();
              }
            },
            columns: [
              { data: 'actions', className: 'text-center', orderable: false },
              { data: 'invoice_number' },
              { data: 'student_name' },
              { data: 'course', className: 'text-wrap' },
              { data: 'course_price', className: 'text-right' },
              { data: 'discount', className: 'text-right' },
              { data: 'total', className: 'text-center' },
              { data: 'paid', className: 'text-center' },
              { data: 'balance', className: 'text-center' },
              { data: 'date_created', className: 'text-wrap' },
              { data: 'due_date', className: 'text-wrap' },
              { data: 'updated_at', className: 'text-wrap' },
            ],
            drawCallback: function () {
              $('#invoicesTable tbody')
                .off()
                .on('click', '.change-status-btn, .status-span', function () {
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
                const form = $(this).closest('form');
                Swal.fire({
                  title: 'Delete invoice?',
                  text: 'Deleting this invoice will unenrolle the student from course!',
                  icon: 'warning',
                  showCancelButton: true,
                  confirmButtonColor: '#d33',
                  cancelButtonColor: '#3085d6',
                  confirmButtonText: 'Delete',
                  cancelButtonText: 'Cancel'
                }).then((result) => {
                  if (result.isConfirmed) {
                    form.submit();
                    $('#invoicesTable').DataTable().ajax.reload();
                  }
                });
              });
            }
          });
        };

        const openStatusChangeModal = (id, status, fullName) => {
          studentId.value = id;
          invoicestatus.value = status;
          studentName.value = fullName;
          showStatusChangeModal.value = true;
        };

        const closeStatusChangeModal = () => {
          showStatusChangeModal.value = false;
        };

        const saveStatusChange = async () => {
          isSaving.value = true;

          try {
            await axios.post(`/updateinvoicestatus/${studentId.value}`, {
              status: invoicestatus.value
            }, {
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
              }
            });

            showAlert('', 'Student status updated successfully.', { icon: 'success' });
            showStatusChangeModal.value = false;
            reloadTable();
          } catch (error) {
            console.error('Error updating status:', error);
            showError('Oops!', 'Something went wrong while updating the status.');
          } finally {
            isSaving.value = false;
          }
        };

        const showError = (
          message = 'Error',
          detail = '',
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
            }
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
            }
          };

          if (message) baseOptions.title = message;
          if (detail) baseOptions.text = detail;

          return Swal.fire(baseOptions);
        };

        return {
          reloadTable,
          saveStatusChange,
          closeStatusChangeModal,
          openStatusChangeModal,
          showStatusChangeModal,
          studentId,
          invoicestatus,
          studentName,
          isSaving
        };
      }
    });

    invoices.mount('#invoices');
    </script>



  <!-- END Hero -->

@endsection
