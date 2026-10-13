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
                            {{ Session::get('message') }}
                        </div>
                    @endif

                    @role(['superAdmin', 'admin', 'financeAdmin'])
                        <div class="dropdown d-inline-block">
                            <button type="button"
                                    class="btn btn-primary rounded-pill px-4"
                                    id="page-header-user-dropdown"
                                    data-bs-toggle="dropdown"
                                    aria-haspopup="true"
                                    aria-expanded="false">
                                <i class="fa fa-cog me-1"></i>
                                <span class="d-sm-inline-block">Action</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-0">
                                <div class="p-2">
                                    @role(['superAdmin', 'admin'])
                                        <a href="{{ url('/addstudent') }}" class="dropdown-item">
                                            <i class="fa fa-fw fa-user-plus me-1"></i> Add student
                                        </a>
                                    @endrole
                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#download-student-report">
                                        <i class="fa fa-download me-1"></i> Students report
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endrole
                </nav>
            </div>
        </div>
    </div>

    <div class="content content-full" id="students">

        @role(['superAdmin', 'admin'])
        {{-- Stat Cards --}}
        <div class="row g-3 mb-4">
            <!-- Active Students -->
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm rounded-3 h-100 stat-card"
                     @click="reloadTable('active')"
                     style="cursor: pointer;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                                 style="width: 42px; height: 42px;">
                                <i class="fa fa-check-circle text-primary"></i>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold fs-4 text-dark lh-1">
                                    {{ \App\Models\Student::where('status', '!=', 'Finished')->count() }}
                                </div>
                                <div class="fs-xs text-muted text-uppercase tracking-wide mt-1">
                                    Active
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Theory Students -->
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm rounded-3 h-100 stat-card"
                     @click="reloadTable('theory')"
                     style="cursor: pointer;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center"
                                 style="width: 42px; height: 42px;">
                                <i class="fa fa-book text-info"></i>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold fs-4 text-dark lh-1">
                                    {{ $theoryCount }}
                                </div>
                                <div class="fs-xs text-muted text-uppercase tracking-wide mt-1">
                                    Theory
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Practical Students -->
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm rounded-3 h-100 stat-card"
                     @click="reloadTable('practical')"
                     style="cursor: pointer;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center"
                                 style="width: 42px; height: 42px;">
                                <i class="fa fa-car text-warning"></i>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold fs-4 text-dark lh-1">
                                    {{ $practicalCount }}
                                </div>
                                <div class="fs-xs text-muted text-uppercase tracking-wide mt-1">
                                    Practical
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unassigned Students -->
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm rounded-3 h-100 stat-card"
                     @click="reloadTable('unassigned')"
                     style="cursor: pointer;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center"
                                 style="width: 42px; height: 42px;">
                                <i class="fa fa-times-circle text-danger"></i>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold fs-4 text-dark lh-1">
                                    {{ \App\Models\Student::whereNull('fleet_id')->whereNull('classroom_id')->where('status', '!=', 'Finished')->count() }}
                                </div>
                                <div class="fs-xs text-muted text-uppercase tracking-wide mt-1">
                                    Floating
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Finished Students -->
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm rounded-3 h-100 stat-card"
                     @click="reloadTable('finished')"
                     style="cursor: pointer;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center"
                                 style="width: 42px; height: 42px;">
                                <i class="fa fa-check-circle text-success"></i>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold fs-4 text-dark lh-1">
                                    {{ \App\Models\Student::where('status', 'Finished')->count() }}
                                </div>
                                <div class="fs-xs text-muted text-uppercase tracking-wide mt-1">
                                    Finished
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cancelled Students -->
            <div class="col-6 col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm rounded-3 h-100 stat-card"
                     @click="reloadTable('cancelled')"
                     style="cursor: pointer;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center"
                                 style="width: 42px; height: 42px;">
                                <i class="fa fa-ban text-dark"></i>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold fs-4 text-dark lh-1">
                                    {{ \App\Models\Student::where('status', 'Cancelled')->count() }}
                                </div>
                                <div class="fs-xs text-muted text-uppercase tracking-wide mt-1">
                                    Cancelled
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endrole

        {{-- Students Table --}}
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <div class="d-flex gap-1">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary rounded-2"
                            data-bs-toggle="tooltip"
                            title="Refresh table"
                            aria-label="Refresh table">
                        <i class="fa fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0 position-relative">

                {{-- Skeleton Loader --}}
                <div id="studentsSkeleton" class="p-3 d-none">
                    <div class="table-responsive">
                        <table class="table table-vcenter align-middle mb-0 fs-sm">
                            <thead class="bg-light">
                                <tr class="text-uppercase fs-xs text-muted tracking-wide">
                                    @for($i = 0; $i < 13; $i++)
                                        <th><div class="skeleton" style="height: 12px; width: 70%;"></div></th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @for($row = 0; $row < 8; $row++)
                                    <tr>
                                        @for($col = 0; $col < 13; $col++)
                                            <td>
                                                <div class="skeleton"
                                                     style="height: 14px; width: {{ rand(50, 95) }}%;"></div>
                                            </td>
                                        @endfor
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Actual Table --}}
                <div class="table-responsive p-3" id="studentsTableWrapper">
                    <table id="studentsTable"
                           class="table table-hover table-vcenter align-middle mb-0 fs-sm"
                           style="width: 100%;">
                        <thead class="bg-light">
                            <tr class="text-uppercase fs-xs text-muted tracking-wide">
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

        @include('students.partials.changeStatusVue')

    </div>

    @role(['superAdmin', 'admin'])
        <!-- Download Student Report Modal -->
        <div class="modal fade" id="download-student-report" tabindex="-1" aria-labelledby="download-student-report" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content border-0 shadow-lg rounded-3">
                    <div class="modal-header bg-primary-dark p-3 border-0">
                        <h5 class="modal-title text-white fw-bold">
                            <i class="fa fa-download me-2"></i>
                            Filter to download report
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <form action="{{ url('/studentsPdf') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-secondary small text-uppercase tracking-wide" for="date">
                                        Date Range
                                    </label>
                                    <select class="form-select rounded-2" id="date" name="date">
                                        <option value="all_time">All Time</option>
                                        <option value="this_week">This Week</option>
                                        <option value="this_month">This Month</option>
                                        <option value="last_3_months">Last 3 Months</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-secondary small text-uppercase tracking-wide" for="balance">
                                        Balance
                                    </label>
                                    <select class="form-select rounded-2" id="balance" name="balance">
                                        <option value="all">All</option>
                                        <option value="balance">With balance</option>
                                        <option value="no_balance">No balance</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-secondary small text-uppercase tracking-wide" for="fleet">
                                        Assigned Car
                                    </label>
                                    <select class="form-select rounded-2" id="fleet" name="fleet">
                                        <option value="alltime" selected>All</option>
                                        @foreach($fleet as $fleet_option)
                                            <option value="{{ $fleet_option->car_registration_number }}">
                                                {{ $fleet_option->car_registration_number }} ({{ $fleet_option->car_brand_model }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold text-secondary small text-uppercase tracking-wide" for="status">
                                        Student Status
                                    </label>
                                    <select class="form-select rounded-2" id="status" name="status">
                                        <option value="allstatus" selected>All</option>
                                        <option value="inprogress">In progress</option>
                                        <option value="finished">Completed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                                    Close
                                </button>
                                <button type="submit" class="btn btn-primary rounded-pill px-4">
                                    <i class="fa fa-download me-1"></i> Download
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endrole

    <script>
        const students = createApp({
            setup() {
                const status = ref('active');
                const showStatusChangeModal = ref(false);
                const studentId = ref(null);
                const studentName = ref('');
                const studentStatus = ref('');
                const completionNotes = ref('');
                const isUpdating = ref(false);

                onMounted(() => {
                    nextTick(() => {
                        setTimeout(() => {
                            getStudents();
                        }, 100);
                    });
                });

                const showSkeleton = () => {
                    $('#studentsSkeleton').removeClass('d-none');
                    $('#studentsTableWrapper').addClass('d-none');
                };

                const hideSkeleton = () => {
                    $('#studentsSkeleton').addClass('d-none');
                    $('#studentsTableWrapper').removeClass('d-none');
                };

                const reloadTable = (val) => {
                    if (val !== undefined) {
                        status.value = val;
                    }

                    showSkeleton();

                    if ($.fn.DataTable.isDataTable('#studentsTable')) {
                        $('#studentsTable').DataTable().ajax.reload(() => {
                            hideSkeleton();
                        });
                    }
                };

                const getStudents = () => {
                    NProgress.start();

                    const table = $('#studentsTable').DataTable();
                    if ($.fn.DataTable.isDataTable('#studentsTable')) {
                        table.destroy();
                    }

                    showSkeleton();

                    $('#studentsTable').DataTable({
                        serverSide: true,
                        processing: false,
                        scrollCollapse: true,
                        scrollX: true,
                        ajax: async function (data, callback, settings) {
                            try {
                                const csrfToken = $('meta[name="csrf-token"]').attr('content');
                                axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
                                const response = await axios.get('/fetch-students', {
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
                                    showError(errorMessage);
                                }
                            } finally {
                                NProgress.done();
                                hideSkeleton();
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

                const openStatusChangeModal = (id, status, fullName) => {
                    studentId.value = id;
                    studentName.value = fullName;
                    studentStatus.value = status;
                    showStatusChangeModal.value = true;
                };

                const closeStatusChangeModal = () => {
                    showStatusChangeModal.value = false;
                };

                const saveStatusChange = async () => {
                    NProgress.start();

                    try {
                        await axios.post(`/updateStudentStatus/${studentId.value}`, {
                            status: studentStatus.value,
                            completionNotes: completionNotes.value
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
                        NProgress.done();
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
                        confirmButtonText: confirmText
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
                        showConfirmButton: false
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
                    studentStatus,
                    studentName,
                    confirmChangeStatus,
                    completionNotes,
                    isUpdating
                };
            }
        });

        students.mount('#students');
    </script>

    <style>
        .stat-card {
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
        }

        /* Skeleton shimmer */
        .skeleton {
            background: linear-gradient(90deg, #e9edf3 25%, #f4f7fb 50%, #e9edf3 75%);
            background-size: 200% 100%;
            border-radius: 0.375rem;
            animation: skeleton-shimmer 1.4s ease-in-out infinite;
            display: inline-block;
        }
        @keyframes skeleton-shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Fade-in the real table when it appears */
        #studentsTableWrapper {
            animation: fadeIn 0.25s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        .tracking-wide {
            letter-spacing: 0.05em;
        }
        .fs-xs {
            font-size: 0.75rem;
        }
        .fs-sm {
            font-size: 0.875rem;
        }
    </style>
@endsection
