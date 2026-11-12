@extends('layouts.backend')

@section('content')
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
                <div>
                    <a href="{{ route('instructors.index') }}" class="fs-sm text-muted"><i class="fa fa-arrow-left me-1"></i> Instructors</a>
                    <h1 class="fs-3 fw-semibold my-2">{{ $instructor->fname }} {{ $instructor->sname }}</h1>
                </div>
                @canany(['edit instructors', 'delete instructors', 'send announcements'])
                <div class="dropdown">
                    <button class="btn btn-primary rounded-pill px-4 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Actions</button>
                    <div class="dropdown-menu dropdown-menu-end">
                        @can('edit instructors')
                        <a class="dropdown-item" href="{{ route('instructors.edit', $instructor->id) }}"><i class="fa fa-edit me-2"></i>Edit Profile</a>
                        @endcan
                        @can('send announcements')
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#announcement-modal" data-instructor-id="{{ $instructor->id }}">
                            <i class="fa fa-bullhorn me-2"></i>Make Announcement
                        </a>
                        @endcan
                        @can('delete instructors')
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('instructors.destroy', $instructor->id) }}" id="delete-instructor-form">
                            @csrf
                            @method('DELETE')
                            <button class="dropdown-item text-danger" type="submit"><i class="fa fa-trash me-2"></i>Delete</button>
                        </form>
                        @endcan
                    </div>
                </div>
                @endcanany
            </div>
        </div>
    </div>

    <div class="content content-full" id="instructor">
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-panel"
                        type="button" role="tab" aria-controls="profile-panel" aria-selected="true">Profile</button>
            </li>
            @can('manage instructor accounts')
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="account-tab" data-bs-toggle="tab" data-bs-target="#account-panel"
                            type="button" role="tab" aria-controls="account-panel" aria-selected="false">Account</button>
                </li>
            @endcan
        </ul>
        <div class="tab-content">
        <div class="tab-pane fade show active" id="profile-panel" role="tabpanel" aria-labelledby="profile-tab">
        <div class="row g-3 mb-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <img class="img-avatar img-avatar96 img-avatar-thumb" src="{{ asset('media/avatars/avatar2.jpg') }}" alt="Instructor avatar">
                            <div>
                                <h2 class="fs-4 fw-semibold mb-2">{{ $instructor->fname }} {{ $instructor->sname }}</h2>
                                <span class="badge bg-{{ strcasecmp($instructor->status ?? '', 'active') === 0 ? 'success' : 'secondary' }}">{{ $instructor->status ?? 'Not set' }}</span>
                                <span class="badge bg-primary ms-1">{{ ucfirst($instructor->department?->name ?? 'No department') }}</span>
                            </div>
                        </div>
                        <dl class="row mb-0 fs-sm">
                            <dt class="col-sm-3 text-muted">Phone</dt><dd class="col-sm-9">{{ $instructor->phone ?: '-' }}</dd>
                            <dt class="col-sm-3 text-muted">Email</dt><dd class="col-sm-9 text-break">{{ $instructor->user?->email ?? '-' }}</dd>
                            <dt class="col-sm-3 text-muted">Address</dt><dd class="col-sm-9 mb-0">{{ $instructor->address ?: '-' }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-3 h-100">
                    <div class="card-body p-4">
                        <h2 class="fs-6 fw-semibold text-uppercase text-muted tracking-wide mb-3">Assignments</h2>
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <div class="bg-light rounded-3 p-3 h-100">
                                    <div class="text-muted fs-sm mb-1"><i class="fa fa-car me-2"></i>Vehicle</div>
                                    <div class="fw-semibold">{{ $instructor->fleet?->car_registration_number ?? 'Not assigned' }}</div>
                                    @if ($instructor->fleet)
                                        <small class="text-muted">{{ $instructor->fleet->car_brand_model }}</small>
                                    @endif
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="bg-light rounded-3 p-3 h-100">
                                    <div class="text-muted fs-sm mb-1"><i class="fa fa-chalkboard me-2"></i>Classrooms</div>
                                    @forelse ($instructor->classrooms as $classroom)
                                        <div class="fw-semibold">{{ $classroom->name }}</div>
                                        <small class="text-muted d-block">{{ $classroom->location }}</small>
                                    @empty
                                        <div>Not assigned</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        @php
                            $isOwnInstructor = auth()->user()->instructor_id && (string) auth()->user()->instructor_id === (string) $instructor->id;
                            $canViewInstructorStudents = auth()->user()->can('view students')
                                && (auth()->user()->can('view all students') || $isOwnInstructor);
                        @endphp
                        @if($canViewInstructorStudents)
                        <div class="row g-3">
                            <div class="col-6">
                                <button type="button" class="btn btn-light w-100 text-start p-3 rounded-3" @click="reloadTable('active')" :aria-pressed="status === 'active'">
                                    <span class="d-block fw-bold fs-4 text-primary">{{ $activeStudents }}</span>
                                    <span class="fs-sm text-muted">Active students</span>
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-light w-100 text-start p-3 rounded-3" @click="reloadTable('finished')" :aria-pressed="status === 'finished'">
                                    <span class="d-block fw-bold fs-4 text-success">{{ $completedStudents }}</span>
                                    <span class="fs-sm text-muted">Finished students</span>
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @php
            $canViewInstructorAttendance = auth()->user()->can('view attendances')
                && (auth()->user()->can('view all attendances')
                    || $isOwnInstructor);
            $canViewInstructorChart = $canViewInstructorAttendance && auth()->user()->can('view schedules')
                && (auth()->user()->can('view all schedules') || $isOwnInstructor);
        @endphp
        @if($canViewInstructorChart)
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap gap-3 align-items-center justify-content-between">
                <h2 class="fs-6 fw-semibold mb-0">Attendance &amp; schedules</h2>
                <div class="d-flex align-items-center gap-2">
                    <label for="chart-month" class="fs-sm text-muted mb-0">Month</label>
                    <input type="month" id="chart-month" class="form-control form-control-sm w-auto"
                           v-model="chartMonth" @change="loadChart" :disabled="chartLoading" required>
                </div>
            </div>
            <div class="card-body p-4">
                <div v-if="chartLoading" class="text-center text-muted" role="status"><span class="spinner-border spinner-border-sm me-2"></span>Loading chart...</div>
                <div v-if="chartError" class="alert alert-danger" role="alert">@{{ chartError }}</div>
                <div v-show="!chartLoading && !chartError" class="position-relative" style="height: 260px;"><canvas id="attendancesChart"></canvas></div>
            </div>
        </div>
        @endif

        @foreach ([
            'students' => ['title' => 'Assigned students', 'headers' => ['Actions', 'Name', 'Course Enrolled', 'Fees', 'Balance', 'Registered on', 'Attendance', 'Course Status', 'Phone', 'Email', 'TRN']],
            'attendances' => ['title' => 'Attendances', 'headers' => ['Date', 'Student', 'Lesson']],
        ] as $table => $config)
            @if(($table === 'students' && $canViewInstructorStudents) || ($table === 'attendances' && $canViewInstructorAttendance))
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap gap-3 align-items-center justify-content-between">
                    <h2 class="fs-6 fw-semibold mb-0">{{ $config['title'] }}</h2>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if ($table === 'students')
                            <select class="form-select form-select-sm w-auto" v-model="status" @change="reloadTable(status)" aria-label="Student status">
                                <option value="active">Active students</option>
                                <option value="finished">Finished students</option>
                            </select>
                        @else
                            @include('attendances.partials.reportSummary')
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-2" @click="refreshTable('{{ $table }}')" title="Refresh {{ strtolower($config['title']) }}" aria-label="Refresh {{ strtolower($config['title']) }}">
                            <i class="fa fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0 position-relative">
                    <div id="{{ $table }}Error" class="alert alert-danger m-3 d-none" role="alert"></div>
                    <div id="{{ $table }}Skeleton" class="table-responsive p-3" aria-hidden="true">
                        <table class="table table-vcenter align-middle mb-0 fs-sm w-100">
                            <thead class="bg-light">
                                <tr>
                                    @foreach ($config['headers'] as $header)
                                        <th><span class="profile-skeleton" style="width: 70%;"></span></th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @for ($row = 0; $row < 8; $row++)
                                    <tr>
                                        @foreach ($config['headers'] as $header)
                                            <td><span class="profile-skeleton" style="width: {{ 45 + (($row * 17 + $loop->index * 13) % 46) }}%;"></span></td>
                                        @endforeach
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                    <div id="{{ $table }}TableWrapper" class="table-responsive p-3 d-none">
                        <table id="{{ $table }}Table" class="table table-hover table-vcenter align-middle mb-0 fs-sm w-100">
                            <thead class="bg-light">
                                <tr class="text-uppercase fs-xs text-muted tracking-wide">
                                    @foreach ($config['headers'] as $header)
                                        <th class="{{ $header === 'Actions' ? 'text-center' : '' }}" style="min-width: {{ in_array($header, ['Name', 'Course Enrolled', 'Date', 'Student']) ? '15rem' : '100px' }};">{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        @endforeach

        </div>
        @can('manage instructor accounts')
            <div class="tab-pane fade" id="account-panel" role="tabpanel" aria-labelledby="account-tab">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h2 class="fs-6 fw-semibold mb-0">User account</h2>
                    </div>
                    <div class="card-body p-4">
                        <x-staff-account-form :user="$instructor->user" profile-label="instructor" />
                    </div>
                </div>
            </div>
        @endcan
        </div>
        @include('students.partials.changeStatusVue')
    </div>

    <style>
        #instructor .tracking-wide { letter-spacing: .05em; }
        #instructor .fs-xs { font-size: .75rem; }
        #instructor .fs-sm { font-size: .875rem; }
        .profile-skeleton {
            display: inline-block;
            height: 14px;
            border-radius: .375rem;
            background: linear-gradient(90deg, #e9edf3 25%, #f4f7fb 50%, #e9edf3 75%);
            background-size: 200% 100%;
            animation: profile-skeleton-shimmer 1.4s ease-in-out infinite;
        }
        @keyframes profile-skeleton-shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
    <script>
        createApp({
            setup() {
                const instructorId = @json($instructor->id);
                const profileUrl = @json(route('instructors.show', $instructor->id));
                const status = ref('active');
                const period = ref('');
                const startDate = ref('');
                const endDate = ref('');
                const chartLoading = ref(true);
                const chartError = ref('');
                const chartMonth = ref(@json(now()->format('Y-m')));
                let attendanceChart = null;
                const showStatusChangeModal = ref(false);
                const studentId = ref(null);
                const studentName = ref('');
                const studentStatus = ref('');
                const completionNotes = ref('');
                const isUpdating = ref(false);
                const tables = {};
                @can('manage instructor accounts')
                @include('components.staff-account-state', [
                    'accountUser' => $instructor->user,
                    'accountUpdateUrl' => route('instructors.account.update', $instructor->id),
                ])
                @endcan

                const notifyError = (text) => Swal.fire({ icon: 'error', title: 'Unable to complete request', text });
                const refreshTable = (name) => tables[name].ajax.reload(null, false);
                const reloadTable = (value = status.value) => {
                    status.value = value;
                    tables.students?.ajax.reload();
                };
                const closeStatusChangeModal = () => { showStatusChangeModal.value = false; };

                const tableOptions = (name, url, params) => ({
                    serverSide: true,
                    processing: true,
                    scrollX: true,
                    scrollCollapse: true,
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    pagingType: 'simple_numbers',
                    ajax: async (data, callback) => {
                        try {
                            const response = await axios.get(url, {
                                params: { ...data, ...params() },
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                            });
                            if (response.data.error) {
                                throw new Error(response.data.error);
                            }
                            $(`#${name}Skeleton`).addClass('d-none');
                            $(`#${name}TableWrapper`).removeClass('d-none');
                            $(`#${name}Error`).addClass('d-none').text('');
                            callback(response.data);
                        } catch (error) {
                            $(`#${name}Error`).removeClass('d-none').text(
                                [401, 403].includes(error.response?.status)
                                    ? 'Session expired or access denied. Reload the page.'
                                    : `Unable to load ${name}. Use Refresh to try again.`
                            );
                        } finally {
                            $(`#${name}Skeleton`).addClass('d-none');
                            $(`#${name}TableWrapper`).removeClass('d-none');
                            $(`#${name}Table_processing`).hide();
                        }
                    },
                    initComplete: function () { this.api().columns.adjust(); }
                });

                const loadChart = async () => {
                    chartError.value = '';
                    if (!chartMonth.value) {
                        chartError.value = 'Please select a month.';
                        return;
                    }
                    chartLoading.value = true;
                    try {
                        const { data } = await axios.get(profileUrl, {
                            params: { section: 'chart', month: chartMonth.value },
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (attendanceChart) attendanceChart.destroy();
                        chartLoading.value = false;
                        await nextTick();
                        attendanceChart = new Chart(document.getElementById('attendancesChart'), {
                            type: 'line',
                            data: {
                                labels: data.labels,
                                datasets: [
                                    { label: 'Attendances', data: data.attendances, borderColor: '#ff9f40', backgroundColor: '#ff9f40', fill: false },
                                    { label: 'Schedules', data: data.schedules, borderColor: '#36a2eb', backgroundColor: '#36a2eb', fill: false }
                                ]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    x: { type: 'time', time: { unit: 'day', displayFormats: { day: 'd MMM' } } },
                                    y: { beginAtZero: true, ticks: { precision: 0 } }
                                }
                            }
                        });
                    } catch (error) {
                        chartError.value = 'Unable to load attendance chart. Select a month to try again.';
                    } finally {
                        chartLoading.value = false;
                    }
                };

                const downloadSummary = () => {
                    if (!period.value) {
                        notifyError('Please select a report period.');
                        return;
                    }
                    const params = new URLSearchParams({ period: period.value });
                    if (period.value === 'custom') {
                        if (!startDate.value || !endDate.value || startDate.value > endDate.value) {
                            notifyError('Choose a valid start and end date.');
                            return;
                        }
                        params.set('start_date', startDate.value);
                        params.set('end_date', endDate.value);
                    }
                    window.open(@json(route('attendanceSummary', $instructor->id)) + '?' + params.toString(), '_blank');
                    bootstrap.Modal.getInstance(document.getElementById('customDateModal'))?.hide();
                };
                const handlePeriodChange = () => {
                    if (period.value === 'custom') {
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('customDateModal')).show();
                    } else if (period.value) {
                        downloadSummary();
                    }
                };

                const confirmChangeStatus = async () => {
                    if (isUpdating.value) return;
                    if (studentStatus.value === 'Finished') {
                        const result = await Swal.fire({
                            title: 'Change status to Finished?',
                            text: 'This will unassign the student from the classroom and car.',
                            icon: 'warning', showCancelButton: true, confirmButtonText: 'Change'
                        });
                        if (!result.isConfirmed) return;
                    }
                    isUpdating.value = true;
                    try {
                        await axios.post(`/updateStudentStatus/${studentId.value}`, {
                            status: studentStatus.value, completionNotes: completionNotes.value
                        });
                        closeStatusChangeModal();
                        reloadTable();
                        Swal.fire({ icon: 'success', text: 'Student status updated successfully.', toast: true, timer: 3000, showConfirmButton: false, position: 'top-end' });
                    } catch (error) {
                        notifyError('Unable to update student status. Please try again.');
                    } finally {
                        isUpdating.value = false;
                    }
                };

                onMounted(() => {
                    $('#profile-tab').on('shown.bs.tab', () => {
                        Object.values(tables).forEach(table => table.columns.adjust());
                        attendanceChart?.resize();
                    });
                    @if($canViewInstructorStudents)
                    tables.students = $('#studentsTable').DataTable({
                        ...tableOptions('students', @json(route('InstructorStudents')), () => ({ status: status.value, instructorId })),
                        order: [[5, 'desc']],
                        columns: [
                            { data: 'actions', className: 'text-center', orderable: false, searchable: false },
                            { data: 'full_name', className: 'text-nowrap', orderable: false },
                            { data: 'course_enrolled', orderable: false },
                            { data: 'fees', className: 'text-end', orderable: false },
                            { data: 'balance', className: 'text-end', orderable: false },
                            { data: 'registered_on', name: 'created_at' },
                            { data: 'attendance', orderable: false },
                            { data: 'course_status', orderable: false },
                            { data: 'phone', name: 'phone' },
                            { data: 'email', orderable: false },
                            { data: 'trn', name: 'trn' }
                        ]
                    });
                    @endif
                    @if($canViewInstructorAttendance)
                    tables.attendances = $('#attendancesTable').DataTable({
                        ...tableOptions('attendances', profileUrl, () => ({ section: 'attendances' })),
                        order: [[0, 'desc']],
                        columns: [
                            {
                                data: 'created_at', name: 'attendances.created_at',
                                render: (value, type) => type === 'display' && value
                                    ? new Date(value).toLocaleString('en-GB', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' })
                                    : value ?? '-'
                            },
                            { data: 'student_name', name: 'student.fname', className: 'text-nowrap' },
                            { data: 'lesson_name', name: 'lesson.name' }
                        ]
                    });
                    @endif
                    $('#studentsTable').on('click', '.change-status-btn, .status-span', function () {
                        const data = $(this).data();
                        studentId.value = data.id;
                        studentName.value = [data.fname, data.mname, data.sname].filter(Boolean).join(' ');
                        studentStatus.value = data.status;
                        completionNotes.value = '';
                        showStatusChangeModal.value = true;
                    });
                    $('#studentsTable').on('click', '.delete-confirm', function (event) {
                        event.preventDefault();
                        const form = $(this).closest('form');
                        Swal.fire({ title: 'Delete student?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' })
                            .then(result => { if (result.isConfirmed) form.trigger('submit'); });
                    });
                    $('#delete-instructor-form').on('submit', function (event) {
                        event.preventDefault();
                        const form = this;
                        Swal.fire({
                            title: 'Delete instructor?',
                            text: 'All lessons belonging to this instructor will be transferred to the Super instructor.',
                            icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete'
                        }).then(result => { if (result.isConfirmed) form.submit(); });
                    });
                    @if($canViewInstructorChart)
                    loadChart();
                    @endif
                });

                return {
                    @can('manage instructor accounts')
                    account, accountSaving, accountErrors, accountMessage, accountError, saveAccount, resetAccount,
                    @endcan
                    status, reloadTable, refreshTable, chartLoading, chartError, chartMonth, loadChart,
                    period, startDate, endDate, handlePeriodChange, downloadSummary,
                    showStatusChangeModal, studentId, studentName, studentStatus,
                    completionNotes, isUpdating, closeStatusChangeModal, confirmChangeStatus
                };
            }
        }).mount('#instructor');
    </script>
@endsection
