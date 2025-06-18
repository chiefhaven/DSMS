@extends('layouts.backend')

@section('content')
    <!-- Hero -->
    <div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Students</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">

            @if(Session::has('message'))
            <div class="alert alert-info">
                {{Session::get('message')}}
            </div>
            @endif

            @role(['superAdmin', 'admin', 'financeAdmin'])
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn btn-primary rounded-pill px-4" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-sm-inline-block">Action</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0">
                        <div class="p-2">
                            @role(['superAdmin', 'admin'])
                                <a href="{{ url('/addstudent') }}" class="dropdown-item nav-main-link">
                                    <i class="fa fa-fw fa-user-plus mr-1"></i>&nbsp; Add student
                                </a>
                            @endrole
                            <button class="dropdown-item nav-main-link" data-bs-toggle="modal" data-bs-target="#download-student-report">
                                <i class="fa fa-download"></i> &nbsp; Students report
                            </button>
                        </div>
                    </div>
                </div>
            @endcan
        </nav>
        </div>
    </div>
    </div>

    <div class="content content-full" id="students">
        @role(['superAdmin', 'admin'])
        <div class="row">
            <!-- Active Students -->
            <div class="col-md-4 col-lg-2 col-6 mb-4">
                <div class="block block-rounded block-link-shadow border" @click="reloadTable('active')" style="cursor: pointer;">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fa fa-2x fa-check-circle text-primary"></i>
                        </div>
                        <div class="ml-3 text-right">
                            <p class="font-size-h3 font-w300 mb-0">
                                {{ \App\Models\Student::where('status', '!=', 'Finished')->count() }}
                            </p>
                            <p class="text-muted mb-0">Active</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Theory Students -->
            <div class="col-md-4 col-lg-2 col-6 mb-4">
                <div class="block block-rounded block-link-shadow border" @click="reloadTable('theory')" style="cursor: pointer;">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fa fa-2x fa-book text-info"></i>
                        </div>
                        <div class="ml-3 text-right">
                            <p class="font-size-h3 font-w300 mb-0">
                                {{ $theoryCount }}
                            </p>
                            <p class="text-muted mb-0">Theory</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Practical Students -->
            <div class="col-md-4 col-lg-2 col-6 mb-4">
                <div class="block block-rounded block-link-shadow border" @click="reloadTable('practical')" style="cursor: pointer;">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fa fa-2x fa-car text-warning"></i>
                        </div>
                        <div class="ml-3 text-right">
                            <p class="font-size-h3 font-w300 mb-0">
                                {{  $practicalCount }}
                            </p>
                            <p class="text-muted mb-0">Practical</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unassigned Students -->
            <div class="col-md-4 col-lg-2 col-6 mb-4">
                <div class="block block-rounded block-link-shadow border" @click="reloadTable('unassigned')" style="cursor: pointer;">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fa fa-2x fa-times-circle text-danger"></i>
                        </div>
                        <div class="ml-3 text-right">
                            <p class="font-size-h3 font-w300 mb-0">
                                {{ \App\Models\Student::whereNull('fleet_id')->whereNull('classroom_id')->where('status', '!=', 'Finished')->count() }}
                            </p>
                            <p class="text-muted mb-0">Not assigned</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Finished Students -->
            <div class="col-md-4 col-lg-2 col-6 mb-4">
                <div class="block block-rounded block-link-shadow border" @click="reloadTable('finished')" style="cursor: pointer;">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fa fa-2x fa-check-circle text-success"></i>
                        </div>
                        <div class="ml-3 text-right">
                            <p class="font-size-h3 font-w300 mb-0">
                                {{ \App\Models\Student::where('status', 'Finished')->count() }}
                            </p>
                            <p class="text-muted mb-0">Finished</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cancelled Students -->
            <div class="col-md-4 col-lg-2 col-6 mb-4">
                <div class="block block-rounded block-link-shadow border" @click="reloadTable('cancelled')" style="cursor: pointer;">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                        <div>
                            <i class="fa fa-2x fa-ban text-secondary"></i>
                        </div>
                        <div class="ml-3 text-right">
                            <p class="font-size-h3 font-w300 mb-0">
                                {{ \App\Models\Student::where('status', 'Cancelled')->count() }}
                            </p>
                            <p class="text-muted mb-0">Cancelled</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        <div class="block block-rounded block-bordered">
            <div class="content-full">
                <div class="row">
                    <div class="col-md-12 py-4">
                        <div class="m-4">
                            <table id="studentsTable" class="table table-bordered table-striped table-vcenter table-responsive">
                                <thead class="thead-dark">
                                    <tr>
                                        <th class="text-center" style="min-width: 100px;">Actions</th>
                                        <th style="min-width: 15rem;">Name</th>
                                        <th style="min-width: 15rem;">Course Enrolled</th>
                                        <th>Fees</th>
                                        <th>Balance</th>
                                        <th style="min-width: 10rem;">Registered on</th>
                                        <th style="min-width: 7rem;">Level</th>
                                        <th style="min-width: 10rem;">Assigned</th>
                                        <th>Attendance</th>
                                        <th style="min-width: 10rem;">Course Status</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>TRN</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('students.partials.changeStatusVue')

    </div>

    @role(['superAdmin', 'admin'])
        <!-- Download Student Report Modal -->
        <div class="modal fade" id="download-student-report" tabindex="-1" aria-labelledby="download-student-report" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="block block-rounded block-themed block-transparent mb-0">
                        <div class="block-header modal-header bg-primary-dark p-3">
                            <h3 class="block-title text-white fs-4 fw-bold">
                                Filter to download report
                            </h3>
                            <div class="block-options">
                                <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                                    <i class="fa fa-fw fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="block-content">
                            <form class="mb-5" action="{{ url('/studentsPdf') }}" method="post" enctype="multipart/form-data" onsubmit="return true;">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-12 mb-4">
                                        <label class="form-label" for="date">Date</label>
                                        <select class="form-select" id="date" name="date">
                                            <option value="all_time">All Time</option>
                                            <option value="this_week">This Week</option>
                                            <option value="this_month">This Month</option>
                                            <option value="last_3_months">Last 3 Months</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-12 mb-4">
                                        <label class="form-label" for="balance">Balance</label>
                                        <select class="form-select" id="balance" name="balance">
                                            <option value="all">All</option>
                                            <option value="balance">With balance</option>
                                            <option value="no_balance">No balance</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-12 mb-4">
                                        <label class="form-label" for="fleet">Assigned Car</label>
                                        <select class="form-select" id="fleet" name="fleet">
                                            <option value="alltime" selected>All</option>
                                            @foreach($fleet as $fleet_option)
                                                <option value="{{$fleet_option->car_registration_number}}">{{$fleet_option->car_registration_number}} ({{$fleet_option->car_brand_model}})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-12 mb-4">
                                        <label class="form-label" for="status">Student Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="allstatus" selected>All</option>
                                            <option value="inprogress">In progress</option>
                                            <option value="finished">Completed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="block-content block-content-full text-end bg-body">
                                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                                        <i class="fa fa-download me-1"></i> Download
                                    </button>
                                    <button type="button" class="btn btn-alt-secondary me-2 rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    <script setup>

        const students = createApp({
        setup() {

            const status = ref('active');
            const showStatusChangeModal = ref(false);
            const studentId = ref(null);
            const studentName = ref('');
            const studentStatus = ref('');
            const completionNotes = ref('');

            onMounted(() => {
                nextTick(() => {
                  setTimeout(() => {
                    getStudents();
                  }, 100);
                });
            });

            const reloadTable = (val) => {
                status.value = val
                if ($.fn.DataTable.isDataTable('#studentsTable')) {
                    $('#studentsTable').DataTable().ajax.reload();
                  }
            }

            const getStudents = () => {
                NProgress.start();
                const table = $('#studentsTable').DataTable();
                if ($.fn.DataTable.isDataTable('#studentsTable')) {
                    table.destroy();
                }
                $('#studentsTable').DataTable({
                  serverSide: true,
                  processing: true,
                  scrollCollapse: true,
                  scrollX: true,
                  ajax: async function(data, callback, settings) {
                    try {
                        const csrfToken = $('meta[name="csrf-token"]').attr('content');
                        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
                        const response = await axios.get('/api/students', {
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
                            showError(errorMessage);
                        }
                    } finally{
                        NProgress.done();
                    }
                },
                  columns: [
                    { data: 'actions', className: 'text-center', orderable: false },
                    { data: 'full_name' },
                    { data: 'course_enrolled', className: 'text-wrap' },
                    { data: 'fees', className: 'text-right' },
                    { data: 'balance', className: 'text-right' },
                    { data: 'registered_on', className: 'text-center' },
                    { data: 'level', className: 'text-center' },
                    { data: 'car_assigned', className: 'text-center' },
                    { data: 'attendance', className: 'text-center' },
                    { data: 'course_status', className: 'text-wrap' },
                    { data: 'phone' },
                    { data: 'email' },
                    { data: 'trn' }
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
                        text: 'Do you want to delete this student?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Delete!',
                        cancelButtonText: 'Cancel'
                      }).then((result) => {
                        if (result.isConfirmed) {
                          form.submit();
                          $('#studentsTable').DataTable().ajax.reload();
                        }
                      });
                    });
                  }
                });
            };

            // Method to open the status change modal
            const openStatusChangeModal = (id, status, fullName) => {
                studentId.value = id;          // Set the student ID
                studentName.value = fullName;  // Set the full name
                studentStatus.value = status;  // Set the student status

                showStatusChangeModal.value = true;  // Show the modal
            };

            // Method to close the modal
            const closeStatusChangeModal = () => {
                showStatusChangeModal.value = false;  // Close the modal
            };

            const saveStatusChange = async () => {
                NProgress.start(); // Start the progress bar

                try {
                    const response = await axios.post(`/updateStudentStatus/${studentId.value}`, {
                        status: studentStatus.value, completionNotes: completionNotes.value
                    }, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    showAlert('', 'Student status updated successfully.', { icon: 'success' });

                    showStatusChangeModal.value = false;
                    reloadTable();
                } catch (error) {
                    showError('Oops!', 'Something went wrong while updating the status.', { confirmText: 'Ok' });
                } finally {
                    NProgress.done(); // Always stop the progress bar
                }
            };

            const confirmChangeStatus = async () => {
                if (studentStatus.value === 'Finished') {
                    const result = await Swal.fire({
                        title: 'Change status to Finished?',
                        text: 'This will unassign the student from the classroom and car.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Change',
                        cancelButtonText: 'Cancel'
                    });

                    if (result.isConfirmed) {
                        saveStatusChange();
                    }
                } else {
                    // For other statuses, just save directly (optional)
                    saveStatusChange();
                }
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
                saveStatusChange,
                closeStatusChangeModal,
                openStatusChangeModal,
                showStatusChangeModal,
                studentId,
                studentStatus,
                openStatusChangeModal,
                closeStatusChangeModal,
                studentName,
                confirmChangeStatus,
                completionNotes,
                isUpdating: ref(false),
            }

        }})

        students.mount('#students');
    </script>

<!-- END Hero -->
@endsection
