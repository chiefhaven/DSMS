@extends('layouts.backend')

@section('content')
<!-- Hero -->
    <div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">{{$instructor->fname}} {{$instructor->sname}}</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
            <ol class="breadcrumb">
            <div class="dropdown d-inline-block">
                <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="d-sm-inline-block">Action</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end p-0">
                <div class="p-2">
                    <form method="GET" action="{{ url('/editinstructor', $instructor->id) }}">
                        {{ csrf_field() }}
                        <button class="dropdown-item" type="submit">Edit</button>
                    </form>
                    <form method="POST" action="{{ url('/deleteinstructor', $instructor->id) }}">
                        {{ csrf_field() }}
                        {{ method_field('DELETE') }}
                        <button class="dropdown-item delete-confirm" type="submit">Delete</button>
                    </form>
                </div>
                </div>
            </div>
            </ol>
        </nav>
        </div>
    </div>
    </div>

    <div class="content content-full" id="instructor">
        <div class="block block-rounded">
            <ul class="nav nav-tabs nav-tabs-block" role="tablist">
                <li class="nav-item">
                <button class="nav-link active" id="instructor-details-tab" data-bs-toggle="tab" data-bs-target="#instructor-details" role="tab" aria-controls="instructor-details" aria-selected="true">
                    Instructor details
                </button>
                </li>
            </ul>
            <div class="block-content pb-4">
                <div class="tab-pane fade active show" id="instructor-details" role="tabpanel" aria-labelledby="instructor-details-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="py-6 px-4"  style="background: #ffffff; border-radius: 0px; border: thin solid #cdcdcd;">
                                <img class="img-avatar img-avatar96 img-avatar-thumb" src="/../media/avatars/avatar2.jpg" alt="">
                                <h1 class="my-2">{{$instructor->fname}} {{$instructor->sname}}</h1>
                                <p>
                                    Address: {{$instructor->address}} <br>Phone: {{$instructor->phone}}<br>Email: {{$instructor->user->email}}
                                </p>
                                <p>
                                    Status: {{$instructor->status}}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="py-6 px-2"  style="background: #ffffff; border-radius: 0px; border: thin solid #cdcdcd;">
                                <table class="table">
                                    <thead>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><b>Assigned:</b></td>
                                            <td>
                                                @if($instructor->fleet)
                                                    <p>
                                                        <b>{{ $instructor->fleet->car_registration_number}}</b><br>
                                                        {{ $instructor->fleet->car_brand_model}}<br>
                                                    </p>
                                                @endif
                                                @if($instructor->classrooms)
                                                    <p>
                                                        @foreach ($instructor->classrooms as $classroom)
                                                            <b>{{ $classroom->name }}</b><br>
                                                            {{ $classroom->location }}
                                                        @endforeach
                                                    </p>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <b>Active Students:</b>
                                            </td>
                                            <td>
                                                {{ ($instructor->fleet && $instructor->fleet->student) || $instructor->classrooms ?
                                                    ($instructor->fleet && $instructor->fleet->student ? $instructor->fleet->student->where('status', '!=', 'Finished')->count() : 0) +
                                                    ($instructor->classrooms ? $instructor->classrooms->sum(function($classroom) {
                                                        return $classroom->students ? $classroom->students->where('status', '!=', 'Finished')->count() : 0;
                                                    }) : 0) : 'None' }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="block block-content block-rounded block-bordered p-4">
                    <!-- Loading Spinner -->
                    <div v-if="isLoading" class="text-center">
                        <span class="spinner-border text-primary" role="status"></span>
                        <p>Loading chart...</p>
                    </div>
                    <div>
                        <canvas id="attendancesChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>


        <div class="row">
            @include('students.partials.changeStatusVue')

            <div class="col-md-12">
                <div class="block block-rounded">
                    <div class="p-4 m-4 h-60 d-flex flex-column overflow-auto">
                        <h5>Assigned students</h5>
                        <hr>
                        <div class="block-content tab-content">
                            <div class="row">
                                <!-- Active Students -->
                                <div class="col-md-4 col-xl-4 col-sm-6">
                                    <div class="block block-rounded block-link-shadow border" @click="reloadTable('active')" style="cursor: pointer;">
                                        <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                                            <div>
                                                <i class="fa fa-2x fa-check-circle text-success"></i>
                                            </div>
                                            <div class="ml-3 text-right">
                                                <p class="font-size-h3 font-w300 mb-0">
                                                    {{ ($instructor->fleet && $instructor->fleet->student) || $instructor->classrooms ?
                                                        ($instructor->fleet && $instructor->fleet->student ? $instructor->fleet->student->where('status', '!=', 'Finished')->count() : 0) +
                                                        ($instructor->classrooms ? $instructor->classrooms->sum(function($classroom) {
                                                            return $classroom->students ? $classroom->students->where('status', '!=', 'Finished')->count() : 0;
                                                        }) : 0) : 'None' }}
                                                </p>
                                                <p class="mb-0">Active</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Finished Students -->
                                <div class="col-md-4 col-xl-4 col-sm-6">
                                    <div class="block block-rounded block-link-shadow border" @click="reloadTable('finished')" style="cursor: pointer;">
                                        <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="fa fa-2x fa-check-circle text-primary"></i>
                                        </div>
                                        <div class="ml-3 text-right">
                                            <p class="font-size-h3 font-w900 mb-0">
                                                {{ ($instructor->fleet && $instructor->fleet->student) || $instructor->classrooms ?
                                                    ($instructor->fleet && $instructor->fleet->student ? $instructor->fleet->student->where('status', '==', 'Finished')->count() : 0) +
                                                    ($instructor->classrooms ? $instructor->classrooms->sum(function($classroom) {
                                                        return $classroom->students ? $classroom->students->where('status', '==', 'Finished')->count() : 0;
                                                    }) : 0) : 'None' }}
                                            </p>
                                            <p class="mb-0">Finished/Cancelled</p>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pending/In Progress Tab -->
                            <div class="tab-pane fade show active" id="pending-in-progress" role="tabpanel" aria-labelledby="pending-in-progress-tab">
                            <div class="content-full">
                                <div class="row table-responsive">
                                <div class="col-md-12 py-4">
                                    <table id="studentsTable" class="table table-bordered table-striped table-vcenter table-responsive">
                                    <thead class="thead-dark">
                                        <tr>
                                        <th class="text-center" style="min-width: 100px;">Actions</th>
                                        <th style="min-width: 15rem;">Name</th>
                                        <th style="min-width: 15rem;">Course Enrolled</th>
                                        <th>Fees</th>
                                        <th>Balance</th>
                                        <th style="min-width: 10rem;">Registered on</th>
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
                        </div> <!-- End of tab-content -->
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="block block-rounded">
                    <div class="p-4 m-4 h-60 d-flex flex-column overflow-auto">
                        <div class="row">
                            <div class="col-md-4">
                                <h5>Attendances</h5>
                            </div>
                            <div class="col-md-8">
                                @include('attendances.partials.reportSummary')
                            </div>
                        </div>
                        <hr>

                        <!-- Show loading spinner when data is fetching -->
                        <div v-if="isLoading" class="text-center">
                            <span class="spinner-border text-primary" role="status"></span>
                            <p>Loading attendances...</p>
                        </div>

                        <!-- Show table when data is loaded -->
                        <table v-if="!isLoading" class="table table-responsive table-striped" id="attendancesTable">
                            <thead>
                                <tr>
                                    <th style="min-width: 200px">Date</th>
                                    <th style="min-width: 200px">Student</th>
                                    <th style="min-width: 100px">Lesson</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="attendance in attendanceData" :key="attendance.id">
                                    <td>@{{ attendance.created_at }}</td>
                                    <td class="text-title">@{{ attendance.student.fname }} @{{ attendance.student.mname ?? '' }} @{{ attendance.student.sname }}</td>
                                    <td class="text">@{{ attendance.lesson.name }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

        const instructor = createApp({
            setup() {
                // Reactive references
                const error = ref(null);
                const studentsData = ref([]);
                const attendanceData = ref([]);
                const schedulesData = ref([]);
                const Schedules = ref([]);
                const instructorId = '{{ $instructor->id ?? null }}';
                const isLoading = ref(false);
                const period = ref('');
                const startDate = ref('');
                const endDate = ref('');
                const status = ref('active');
                const showStatusChangeModal = ref(false);
                const studentId = ref(null);
                const studentName = ref('');
                const studentStatus = ref('');


                const formatCurrency = (value) => {
                    return `K ${Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                };

                const formatDate = (date) => {
                    if (!date) return '';
                    return new Intl.DateTimeFormat('en-US', {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric',
                    }).format(new Date(date));
                };

                const handlePeriodChange = () => {
                    if (period.value === 'custom') {
                        let modal = new bootstrap.Modal(document.getElementById('customDateModal'));
                        modal.show();
                    } else {
                        downloadSummary(instructorId);
                    }
                };

                const reloadTable = (val) => {
                    NProgress.start();

                    status.value = val
                    if ($.fn.DataTable.isDataTable('#studentsTable')) {
                        $('#studentsTable').DataTable().ajax.reload();
                      }

                    NProgress.done();

                }

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
                            const response = await axios.get('/api/instructor-students', {
                                params: { ...data, status: status.value, instructorId: instructorId },
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
                                notification('Session expired, reloading...');
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                notification(errorMessage);
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

                // Fetch instructor's students when the component mounts
                onMounted(() => {

                    getStudents();
                    data(instructorId);

                });

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
                    NProgress.start();

                    try {
                        const response = await axios.post(`/updateStudentStatus/${studentId.value}`, {
                            status: studentStatus.value
                        }, {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        showAlert('', 'Student status updated successfully.', { icon: 'success' });
                        showStatusChangeModal.value = false;
                        status.value ='active';
                        reloadTable();

                    } catch (error) {
                        console.error('Error updating status:', error);
                        showError('Oops!', 'Something went wrong while updating the status.', {
                            confirmText: 'Ok'
                        });
                    } finally {
                        NProgress.done();
                    }
                };


                const completedStudents = computed(() => {
                    return studentsData.value.filter(student => student.status === 'Finished');
                });

                const activeStudents = computed(() => {
                    return studentsData.value.filter(student => student.status != 'Finished');
                });

                const notification = ($text, $icon) =>{
                    Swal.fire({
                        toast: true,
                        position: "top-end",
                        html: $text,
                        showConfirmButton: false,
                        timer: 5500,
                        timerProgressBar: true,
                        icon: $icon,
                        didOpen: (toast) => {
                            toast.onmouseenter = Swal.stopTimer;
                            toast.onmouseleave = Swal.resumeTimer;
                          }
                      });
                }

                // Chart Data
                const labels = ref([]);
                const Attendances = ref([]);
                let attendancesChart = null;
                const chartLoading = ref(false);

                const getXlsxData = async () => {
                    try {
                        const data = attendanceData.value;
                        const schedules = schedulesData.value;

                        const currentMonth = new Date().getMonth();
                        const currentYear = new Date().getFullYear();

                        // Group attendance by date
                        const dailyData = data.reduce((acc, curr) => {
                            const attendanceDate = new Date(curr.attendance_date);
                            if (isNaN(attendanceDate)) return acc;

                            if (attendanceDate.getMonth() === currentMonth && attendanceDate.getFullYear() === currentYear) {
                                const date = attendanceDate.toISOString().split('T')[0];
                                acc[date] = (acc[date] || 0) + 1;
                            }
                            return acc;
                        }, {});

                        // Group schedules by date
                        const dailySchedules = schedules.reduce((acc, curr) => {
                            const scheduleDate = new Date(curr.start_time);
                            if (isNaN(scheduleDate)) return acc;

                            if (scheduleDate.getMonth() === currentMonth && scheduleDate.getFullYear() === currentYear) {
                                const date = scheduleDate.toISOString().split('T')[0];
                                acc[date] = (acc[date] || 0) + 1;
                            }
                            return acc;
                        }, {});

                        // Merge and sort unique dates
                        const allDates = Array.from(new Set([
                            ...Object.keys(dailyData),
                            ...Object.keys(dailySchedules)
                        ])).sort();

                        // Store dates and values
                        labels.value = allDates; // ISO strings: yyyy-MM-dd
                        Attendances.value = allDates.map(date => dailyData[date] || 0);
                        Schedules.value = allDates.map(date => dailySchedules[date] || 0);

                        console.log('Filtered Data:', labels.value, Attendances.value, Schedules.value);

                        nextTick(() => {
                            loadChart();
                        });
                    } catch (err) {
                        console.error("Error fetching attendance data:", err);
                    }
                };


                const loadChart = () => {
                    chartLoading.value = false;
                    const ctx = document.getElementById("attendancesChart");

                    if (!ctx) {
                        console.error("Canvas element not found");
                        return;
                    }

                    if (attendancesChart) {
                        attendancesChart.destroy(); // Destroy existing chart before reloading
                    }

                    attendancesChart = new Chart(ctx, {
                        type: "line",
                        data: {
                            labels: labels.value, // Use ISO dates
                            datasets: [
                                {
                                    label: "Attendances",
                                    fill: false,
                                    data: Attendances.value,
                                    backgroundColor: "rgba(255, 159, 64, 0.5)",
                                    borderColor: "rgba(255, 159, 64, 1)",
                                    borderWidth: 2,
                                },
                                {
                                    label: "Schedules",
                                    fill: false,
                                    data: Schedules.value,
                                    backgroundColor: "rgba(54, 162, 235, 0.5)",
                                    borderColor: "rgba(54, 162, 235, 1)",
                                    borderWidth: 2,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: "top",
                                },
                                datalabels: {
                                    display: true,
                                    color: "#000",
                                    align: "top",
                                    formatter: (value) => (value > 0 ? value : ""), // Only show non-zero values
                                },
                            },
                            scales: {
                                x: {
                                    type: "time",
                                    time: {
                                        parser: "yyyy-MM-dd",
                                        unit: "day",
                                        displayFormats: {
                                            day: "d MMM",
                                        },
                                        tooltipFormat: "d MMM yyyy",
                                    },
                                    title: {
                                        display: true,
                                        text: "Date",
                                    },
                                    ticks: {
                                        autoSkip: true,
                                        maxRotation: 45,
                                        minRotation: 45,
                                    },
                                },
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: "Count",
                                    },
                                },
                            },
                        },
                    });
                };


                const data = async (instructor) => {
                    NProgress.start();
                    error.value = null; // Reset error state
                    isLoading.value = true;


                    try {
                        const response = await axios.get(`/instructor-data/${instructor}`);

                        if (response.status === 200) {
                            const data = response.data;

                            // Reset arrays before updating
                            studentsData.value = [];
                            attendanceData.value = [];
                            schedulesData.value = [];

                            // Process classrooms
                            if (data.classrooms && Array.isArray(data.classrooms)) {
                                data.classrooms.forEach((classroom, index) => {
                                    if (Array.isArray(classroom.students)) {
                                        studentsData.value.push(...classroom.students);
                                    }
                                });
                            }

                            // Process fleet students (Merging instead of overwriting)
                            if (data.fleet && Array.isArray(data.fleet.student)) {
                                studentsData.value.push(...data.fleet.student);
                            }

                            // Process attendance
                            if (data.attendances && Array.isArray(data.attendances)) {
                                attendanceData.value = data.attendances;
                                schedulesData.value = data.attendances;
                                getXlsxData();

                            }

                            // Apply DataTables after ensuring elements exist
                            setTimeout(() => {

                                if ($.fn.DataTable.isDataTable("#attendancesTable")) {
                                    $("#attendancesTable").DataTable().destroy();
                                }

                                $.extend($.fn.dataTable.ext.type.order, {
                                    "custom-date-pre": function (data) {
                                        // Handle ISO 8601 date format: "2023-08-05T17:09:47.000000Z"
                                        const parsedDate = new Date(data);
                                        return parsedDate.getTime(); // Convert to timestamp for sorting
                                    }
                                });

                                // Function to format date to "9 March, 2025 23:22:00"
                                function formatDate(dateString) {
                                    const date = new Date(dateString);

                                    // Define month names
                                    const months = [
                                        "January", "February", "March", "April", "May", "June",
                                        "July", "August", "September", "October", "November", "December"
                                    ];

                                    const day = date.getDate();
                                    const month = months[date.getMonth()];
                                    const year = date.getFullYear();

                                    const hours = String(date.getHours()).padStart(2, '0');
                                    const minutes = String(date.getMinutes()).padStart(2, '0');
                                    const seconds = String(date.getSeconds()).padStart(2, '0');

                                    return `${day} ${month}, ${year} ${hours}:${minutes}:${seconds}`;
                                }

                                $("#attendancesTable").DataTable({
                                    order: [[0, 'desc']], // Sort by Date column
                                    columnDefs: [
                                        { targets: 0, type: 'custom-date' } // Apply custom date sorting
                                    ],
                                    drawCallback: function () {
                                        // Format all visible date cells on pagination change
                                        $("#attendancesTable tbody tr").each(function () {
                                            const cell = $(this).find("td:first");
                                            const originalDate = cell.text().trim();
                                            if (originalDate) {
                                                cell.text(formatDate(originalDate));
                                            }
                                        });
                                    }
                                });

                            }, 500);
                        } else {
                            throw new Error("Unexpected response status");
                        }
                    } catch (err) {
                        console.error("Error fetching students:", err);
                        error.value = "Failed to fetch data";
                    } finally {
                        NProgress.done();
                        isLoading.value = false;
                    }


                };

                return {
                    data,
                    studentsData,
                    attendanceData,
                    schedulesData,
                    error,
                    formatDate,
                    isLoading,
                    Attendances,
                    labels,
                    chartLoading,
                    period,
                    startDate,
                    endDate,
                    handlePeriodChange,
                    downloadSummary,
                    completedStudents,
                    activeStudents,
                    reloadTable,
                    saveStatusChange,
                    closeStatusChangeModal,
                    openStatusChangeModal,
                    showStatusChangeModal,
                    studentId,
                    studentStatus,
                    openStatusChangeModal,
                    closeStatusChangeModal,
                    studentName
                };
            }
        });

        instructor.mount('#instructor');
    </script>

@endsection
