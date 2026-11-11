@extends('layouts.backend')

@section('content')
    <!-- Hero -->
    <div class="bg-body-light" id="attendancesReport">
        <div class="content content-full">
            <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Attendances</h1>

                @role('instructor')
                    <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
                        @include('attendances.partials.reportSummary')
                    </nav>
                @endrole
            </div>
        </div>
    </div>

    <div class="content content-full" id="attendances">
        @if ($errors->any())
            <script>
                Swal.fire({
                    title: "{{ __('Success!') }}",
                    text: "{{ session('toast_success') }}",
                    icon: "success"
                });
            </script>
        @endif

        @include('components.alert')

        {{-- Attendances Table --}}
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa fa-clipboard-check me-2"></i>
                    Attendance Records
                </h6>
                <button type="button"
                        class="btn btn-sm btn-outline-secondary rounded-2"
                        data-bs-toggle="tooltip"
                        title="Refresh table"
                        aria-label="Refresh table"
                        @click="reloadTable">
                    <i class="fa fa-sync-alt"></i>
                </button>
            </div>

            <div class="card-body p-0 position-relative">

                {{-- Skeleton Loader --}}
                <div id="attendancesSkeleton" class="p-3 d-none">
                    <div class="table-responsive">
                        <table class="table table-vcenter align-middle mb-0 fs-sm">
                            <thead class="bg-light">
                                <tr class="text-uppercase fs-xs text-muted tracking-wide">
                                    @for($i = 0; $i < 6; $i++)
                                        <th><div class="skeleton" style="height: 12px; width: 70%;"></div></th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @for($row = 0; $row < 8; $row++)
                                    <tr>
                                        @for($col = 0; $col < 6; $col++)
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
                <div class="table-responsive p-3" id="attendancesTableWrapper">
                    <table id="attendancesTable"
                           class="table table-hover table-vcenter align-middle mb-0 fs-sm"
                           style="width: 100%;">
                        <thead class="bg-light">
                            <tr class="text-uppercase fs-xs text-muted tracking-wide">
                                <th class="text-center" style="min-width: 7rem;">Actions</th>
                                <th style="min-width: 14rem;">Date</th>
                                <th style="min-width: 14rem;">Student</th>
                                <th style="min-width: 16rem;">Lesson</th>
                                <th style="min-width: 14rem;">Entered by</th>
                                <th style="min-width: 8rem;">Anomaly</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Report Modal -->
    <div class="modal fade" id="summary" tabindex="-1" aria-labelledby="summaryLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header bg-primary-dark p-3 border-0">
                    <h5 class="modal-title text-white fw-bold" id="summaryLabel">
                        <i class="fa fa-download me-2"></i> Download Report
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="{{ url('/downloadSummary') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary small text-uppercase tracking-wide" for="period">
                                Period
                            </label>
                            <select class="form-select rounded-2" id="period" name="period">
                                <option value="today">Today</option>
                                <option value="this_week">This Week</option>
                                <option value="this_month">This Month</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                        <x-form-actions modal>
                            <button type="submit" class="btn btn-primary rounded-pill px-4">
                                <i class="fa fa-download me-1"></i> Download
                            </button>
                        </x-form-actions>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ---------------------------------------------------------------
        // Report summary Vue app (hero area)
        // ---------------------------------------------------------------
        const attendancesReport = createApp({
            setup() {
                const period = ref('today');
                const startDate = ref('');
                const endDate = ref('');
                const instructorId = '{{ $instructor->id ?? null }}';

                const handlePeriodChange = () => {
                    if (period.value === 'custom') {
                        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('customDateModal'));
                        modal.show();
                    } else {
                        downloadSummary(instructorId);
                    }
                };

                const downloadSummary = async (instructor) => {
                    if (!period.value) {
                        notification("Please select a period.", "error");
                        return;
                    }

                    let url = `/attendanceSummary/${instructor}?period=${period.value}`;

                    if (period.value === 'custom') {
                        if (!startDate.value || !endDate.value) {
                            notification("Please select both start and end dates.", "error");
                            return;
                        }
                        url += `&start_date=${startDate.value}&end_date=${endDate.value}`;
                    }

                    NProgress.start();
                    try {
                        window.open(url, '_blank');
                        document.getElementById('customDateModal')?.querySelector('.btn-close')?.click();
                    } catch (err) {
                        console.error("Failed to fetch data", err);
                    } finally {
                        NProgress.done();
                    }
                };

                const notification = (text, icon) => {
                    Swal.fire({
                        toast: true,
                        position: "top-end",
                        html: text,
                        showConfirmButton: false,
                        timer: 5500,
                        timerProgressBar: true,
                        icon: icon,
                        didOpen: (toast) => {
                            toast.onmouseenter = Swal.stopTimer;
                            toast.onmouseleave = Swal.resumeTimer;
                        }
                    });
                };

                return { period, startDate, endDate, handlePeriodChange, downloadSummary };
            }
        });

        attendancesReport.mount('#attendancesReport');

        // ---------------------------------------------------------------
        // Attendances table Vue app
        // ---------------------------------------------------------------
        const attendances = createApp({
            setup() {
                const loadingData = ref(false);
                window.userIsInstructor = @json(auth()->user()->hasRole('instructor'));

                const showSkeleton = () => {
                    $('#attendancesSkeleton').removeClass('d-none');
                    $('#attendancesTableWrapper').addClass('d-none');
                };

                const hideSkeleton = () => {
                    $('#attendancesSkeleton').addClass('d-none');
                    $('#attendancesTableWrapper').removeClass('d-none');
                };

                const showToast = (message, icon = 'success') => {
                    Swal.fire({
                        icon,
                        title: message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                    });
                };

                const deleteAttendance = async (attendance) => {
                    const result = await Swal.fire({
                        title: 'Delete attendance?',
                        text: 'Are you sure you want to delete this attendance?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Delete',
                    });

                    if (!result.isConfirmed) return;

                    try {
                        NProgress.start();
                        await axios.delete(`/api/attendance/${attendance.id}`);
                        showToast('Attendance deleted.');
                        $('#attendancesTable').DataTable().ajax.reload();
                    } catch (error) {
                        console.error(error);
                        showToast(error.response?.data?.message || 'An error occurred.', 'error');
                    } finally {
                        NProgress.done();
                        loadingData.value = false;
                    }
                };

                const getAttendances = () => {
                    NProgress.start();
                    loadingData.value = true;

                    if ($.fn.DataTable.isDataTable('#attendancesTable')) {
                        $('#attendancesTable').DataTable().destroy();
                    }

                    showSkeleton();

                    $('#attendancesTable').DataTable({
                        serverSide: true,
                        processing: false,
                        scrollCollapse: true,
                        scrollX: true,
                        ajax: async function (data, callback) {
                            try {
                                const response = await axios.get('/api/fetch-attendances', { params: data });

                                // Flag duplicates by student + lesson + full timestamp
                                const seen = {};
                                const processedData = response.data.data.map(att => {
                                    const key = `${att.student_id}-${att.lesson_id}-${att.created_at}`;
                                    att.anomaly = att.instructor_id && seen[key] ? true : false;
                                    if (att.instructor_id && !seen[key]) seen[key] = true;
                                    return att;
                                });

                                callback({ ...response.data, data: processedData });
                            } catch (error) {
                                console.error('Error loading attendances:', error);
                                callback({ data: [], recordsTotal: 0, recordsFiltered: 0 });
                            } finally {
                                loadingData.value = false;
                                NProgress.done();
                                hideSkeleton();
                            }
                        },
                        order: [[1, 'desc']],
                        columns: [
                            { data: 'actions', className: 'text-wrap', name: 'actions', orderable: false, searchable: false },
                            { data: 'attendance_date', className: 'text-wrap', name: 'attendance_date' },
                            { data: 'student', className: 'text-wrap', name: 'students' },
                            { data: 'lesson', className: 'text-wrap', name: 'lesson' },
                            {
                                data: 'instructor',
                                className: 'text-wrap',
                                name: 'instructor',
                                visible: !window.userIsInstructor
                            },
                            {
                                data: 'anomaly',
                                className: 'text-center',
                                render: data => data
                                    ? '<span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle rounded-pill px-2">Duplicate</span>'
                                    : '',
                                orderable: false,
                                searchable: false
                            }
                        ],
                        language: {
                            emptyTable: "No attendance records found."
                        },
                        drawCallback: function () {
                            // Tooltips on any rendered badges/buttons
                            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
                                bootstrap.Tooltip.getOrCreateInstance(el);
                            });
                        }
                    });
                };

                const reloadTable = () => {
                    showSkeleton();
                    if ($.fn.DataTable.isDataTable('#attendancesTable')) {
                        $('#attendancesTable').DataTable().ajax.reload(() => hideSkeleton());
                    }
                };

                onMounted(() => getAttendances());

                return { loadingData, deleteAttendance, reloadTable };
            },
        });

        window.attendanceApp = attendances.mount('#attendances');

        window.openEditAttendance = el => {
            const attendance = JSON.parse(el.dataset.attendance);
            window.attendanceApp.openEditModal?.(attendance);
        };

        window.openDeleteAttendance = attendance => {
            window.attendanceApp.deleteAttendance(attendance);
        };
    </script>

    <style>
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
        #attendancesTableWrapper {
            animation: fadeIn 0.25s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        .tracking-wide { letter-spacing: 0.05em; }
        .fs-xs { font-size: 0.75rem; }
        .fs-sm { font-size: 0.875rem; }

        /* Slightly tighter hover row for the new card style */
        #attendancesTable.table-hover > tbody > tr:hover > * {
            --bs-table-accent-bg: rgba(13, 110, 253, 0.04);
        }
    </style>
@endsection
