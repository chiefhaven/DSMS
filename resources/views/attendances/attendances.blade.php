@extends('layouts.backend')

@section('content')
  <!-- Hero -->
    <div class="bg-body-light" id="attendancesReport">
        <div class="content content-full">
            <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
            <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Attendances</h1>
            <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
                <ol class="breadcrumb">
                @role(['instructor'])
                    <div class="col-md-12 block-rounded block-bordered p-4 dropdown d-inline-block">
                        @include('attendances.partials.reportSummary')
                    </div>
                @endcan
                </ol>
            </nav>
            </div>
        </div>
    </div>

    <div class="content content-full">
        @if ($errors->any())
            <script>
                swal.fire({
                    title: "{{ __('Success!') }}",
                    text: "{{ session('toast_success') }}",
                    type: "success"
                });
            </script>
        @endif
        @include('components.alert')
        <div class="block block-rounded block-bordered" id="attendances">
            <div class="block-content">
                    <div class="table-responsive">
                        <table id="attendancesTable" class="table table-striped table-bordered" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th class="text-center">Actions</th>
                                    <th style="min-width: 18em;">Date</th>
                                    <th style="min-width: 18em;">Student</th>
                                    <th style="min-width: 20%;">Lesson</th>

                                    <th style="min-width: 20%;">Entered by</th>
                                    <th>Anomaly</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
            </div>
        </div>

        <!-- Attendance Report Modal -->
        <div class="modal" id="summary" tabindex="-1" aria-labelledby="summary" style="display: none;" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="block block-rounded block-themed block-transparent mb-0">
                <div class="block-header bg-primary-dark">
                    <h3 class="block-title">Download Report</h3>
                    <div class="block-options">
                    <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-fw fa-times"></i>
                    </button>
                    </div>
                </div>
                <div class="block-content">
                    <form class="mb-5" action="{{ url('/downloadSummary') }}" method="post" enctype="multipart/form-data" onsubmit="return true;">
                        @csrf
                        <div class="row haven-floating">
                            <div class="col-7 form-floating mb-4">

                                <label for="period">Period</label>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            </div>
            </div>
        </div>
    </div>
    <script>

        const attendancesReport = createApp({
            setup() {
                const period = ref('today');
                const startDate = ref('');
                const endDate = ref('');
                const instructorId = '{{ $instructor->id ?? null }}';

                const handlePeriodChange = () => {
                    if (period.value === 'custom') {
                        let modal = new bootstrap.Modal(document.getElementById('customDateModal'));
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
                        document.getElementById('customDateModal').querySelector('.btn-close').click(); // Close modal
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

                return {
                    period,
                    startDate,
                    endDate,
                    handlePeriodChange,
                    downloadSummary
                };
            }
        });

        attendancesReport.mount('#attendancesReport');
    </script>

    <script>

    </script>

    <script setup>
        const attendances = createApp({
            setup() {
                const loadingData = ref(false);
                window.userIsInstructor = @json(auth()->user()->hasRole('instructor'));

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
                    Swal.fire({
                        title: 'Delete attendance?',
                        text: 'Are you sure you want to delete this attendance?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Delete',
                    }).then(async (result) => {
                        if (result.isConfirmed) {
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
                        }});
                };

                const getAttendances = () => {
                    NProgress.start();
                    loadingData.value = true;

                    // Destroy any existing DataTable instance
                    if ($.fn.DataTable.isDataTable('#attendancesTable')) {
                        $('#attendancesTable').DataTable().destroy();
                    }

                    $('#attendancesTable').DataTable({
                        serverSide: true,
                        processing: true,
                        scrollCollapse: true,
                        scrollX: true,
                        ajax: async function (data, callback, settings) {
                            try {
                                const response = await axios.get('/api/fetch-attendances', { params: data });

                                // Track duplicates by student + lesson + full timestamp, only if instructor_id exists
                                const seen = {};
                                const processedData = response.data.data.map(att => {
                                    const studentId = att.student_id;
                                    const lessonId = att.lesson_id;
                                    const timestamp = att.created_at; // full datetime including seconds

                                    const key = `${studentId}-${lessonId}-${timestamp}`;

                                    // Only mark anomaly if instructor_id is set
                                    att.anomaly = att.instructor_id && seen[key] ? true : false;

                                    if (att.instructor_id && !seen[key]) seen[key] = true;

                                    return att;
                                });

                                callback({
                                    ...response.data,
                                    data: processedData
                                });
                            } catch (error) {
                                console.error('Error loading attendances:', error);
                                callback({ data: [], recordsTotal: 0, recordsFiltered: 0 });
                            } finally {
                                loadingData.value = false;
                                NProgress.done();
                            }
                        },
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
                                render: data => data ? '<span class="badge bg-danger">Duplicate</span>' : '',
                                orderable: false,
                                searchable: false
                            }
                        ],
                        language: {
                            emptyTable: "No bulk attendance records found."
                        }
                    });



                };


                onMounted(() => { getAttendances(); });

                return {
                    loadingData, deleteAttendance
                };
            },
        });

            window.attendanceApp = attendances.mount('#attendances');

            window.openEditAttendance = el => {
            const attendance = JSON.parse(el.dataset.attendance);
            window.attendanceApp.openEditModal(attendance);
        };

            window.openDeleteAttendance = attendance => {
            window.attendanceApp.deleteAttendance(attendance);
        };
    </script>
<!-- END Hero -->
@endsection
