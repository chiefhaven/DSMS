@extends('layouts.backend')

@section('content')
<!-- Hero -->
    <div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
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
                                            <td><b>Active Students:</b></td>
                                            <td>
                                                {{ ($instructor->fleet && $instructor->fleet->student) || $instructor->classrooms ?
                                                    ($instructor->fleet && $instructor->fleet->student ? $instructor->fleet->student->where('status', '!=', 'Finished')->count() : 0) +
                                                    ($instructor->classrooms ? $instructor->classrooms->sum(function($classroom) {
                                                        return $classroom->students ? $classroom->students->where('status', '!=', 'Finished')->count() : 0;
                                                    }) : 0) : 'None' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><b>Todays attendances:</b></td>
                                            <td>{{ $instructor->attendances->count() ?? '0' }}</td>
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
            <div class="col-md-6">
                <div class="block block-rounded">
                    <div class="p-4 m-4 h-60 d-flex flex-column overflow-auto">

                        <h5>Assigned students</h5>
                        <hr>

                        <!-- Show loading spinner when data is fetching -->
                        <div v-if="isLoading" class="text-center">
                            <span class="spinner-border text-primary" role="status"></span>
                            <p>Loading students...</p>
                        </div>

                        <!-- Show table when data is loaded -->
                        <table v-else class="table table-responsive table-striped" id="studentsTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(student, index) in studentsData" :key="student.id">
                                    <td class="text-uppercase">@{{ student.fname }} @{{ student.mname }} @{{ student.sname }}</td>
                                    <td class="text-uppercase">@{{ student.status }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
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
                        <table v-else class="table table-responsive table-striped" id="attendancesTable">
                            <thead>
                                <tr>
                                    <th style="min-width: 200px">Date</th>
                                    <th style="min-width: 200px">Student</th>
                                    <th style="min-width: 200px">Lesson</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(attendance, index) in attendanceData" :key="attendance.id">
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
        const { createApp, ref, computed, onMounted, watch, onBeforeUnmount, reactive, nextTick } = Vue;

        const instructor = createApp({
            setup() {
                // Reactive references
                const error = ref(null);
                const studentsData = ref([]);
                const attendanceData = ref([]);
                const instructorId = '{{ $instructor->id ?? null }}';
                const isLoading = ref(false);
                const period = ref('');
                const startDate = ref('');
                const endDate = ref('');

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
                        downloadSummary('{{ $instructor->id }}');
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

                // Fetch instructor's students when the component mounts
                onMounted(() => {
                    data(instructorId);
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

                        // Get the current date details
                        const currentMonth = new Date().getMonth(); // Get current month (0 - 11)
                        const currentYear = new Date().getFullYear(); // Get current year (YYYY)

                        // Group by date and count attendances, but filter by current month and year
                        const dailyData = data.reduce((acc, curr) => {
                            const attendanceDate = new Date(curr.attendance_date);
                            const date = attendanceDate.toISOString().split('T')[0]; // Extract date (YYYY-MM-DD)

                            // Check if the attendance date is within this month and year
                            if (attendanceDate.getMonth() === currentMonth && attendanceDate.getFullYear() === currentYear) {
                                if (!acc[date]) {
                                    acc[date] = 0;
                                }
                                acc[date] += 1; // Count attendance per date
                            }

                            return acc;
                        }, {});

                        // Prepare labels (dates) and attendances (counts)
                        labels.value = Object.keys(dailyData).map(date => {
                            const formattedDate = new Date(date);
                            const options = { day: 'numeric', month: 'long' };
                            return new Intl.DateTimeFormat('en-GB', options).format(formattedDate); // 'en-GB' ensures the month is in English
                        });

                        // Convert date strings to Date objects
                        Attendances.value = Object.values(dailyData);  // Extract attendance counts per date
                        console.log('Filtered Data:', labels.value, Attendances.value);

                        // Wait until the DOM is updated and then load the chart
                        nextTick(() => {
                            loadChart(); // Render the chart after the DOM is updated
                        });
                    } catch (err) {
                        console.error("Error fetching attendance data:", err);
                    }
                };

                // Load Chart
                const loadChart = () => {
                    chartLoading.value = false;
                    const ctx = document.getElementById("attendancesChart");

                    if (!ctx) {
                        console.error("Canvas element not found");
                        return; // Exit if the canvas element is not found
                    }

                    if (attendancesChart) {
                        attendancesChart.destroy(); // Destroy existing chart before reloading
                    }

                    attendancesChart = new Chart(ctx, {
                        type: "line",
                        data: {
                            labels: labels.value,
                            datasets: [
                                {
                                    label: "Instructor attendances",
                                    fill: false,
                                    data: Attendances.value,
                                    backgroundColor: "rgb(255, 159, 64)",
                                    borderColor: "rgba(255, 159, 64, 0.8)",
                                    borderWidth: 3,
                                    radius: 0,
                                    datalabels: {
                                        anchor: 'end',
                                        align: 'top',
                                        font: {
                                            size: 14,
                                            weight: 'light',
                                        },
                                        color: 'grey',
                                        formatter: (value) => value, // Display the attendance count value
                                    },
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                datalabels: {
                                    display: true, // Ensure datalabels are shown
                                },
                            },
                            scales: {
                                x: {
                                    type: "time",
                                    time: {
                                        unit: "day",
                                        tooltipFormat: "ll", // Better date format for tooltip
                                        displayFormats: {
                                            day: "D MMM", // Display date format
                                        },
                                    },
                                    title: { display: true, text: "Date" },
                                    ticks: {
                                        autoSkip: false,
                                        stepSize: 1,
                                        maxRotation: 60,
                                        minRotation: 60,
                                    },
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return value; // Display the y-axis values
                                        }
                                    }
                                },
                            },
                        },
                        plugins: [ChartDataLabels], // Use the datalabels plugin
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
                            console.log("Fetched Data:", data);

                            // Reset arrays before updating
                            studentsData.value = [];
                            attendanceData.value = [];

                            // Process classrooms
                            if (data.classrooms && Array.isArray(data.classrooms)) {
                                data.classrooms.forEach((classroom, index) => {
                                    if (Array.isArray(classroom.students)) {
                                        studentsData.value.push(...classroom.students);
                                        console.log(`Classroom ${index + 1}:`, classroom.students);
                                    }
                                });
                            }

                            // Process fleet students (Merging instead of overwriting)
                            if (data.fleet && Array.isArray(data.fleet.student)) {
                                studentsData.value.push(...data.fleet.student);
                                console.log("Fleet students added:", data.fleet.student);
                            }

                            // Process attendance
                            if (data.attendances && Array.isArray(data.attendances)) {
                                attendanceData.value = data.attendances;
                                getXlsxData();

                                console.log("Attendances:", attendanceData.value);
                            }

                            // Apply DataTables after ensuring elements exist
                            setTimeout(() => {
                                if ($.fn.DataTable.isDataTable("#studentsTable")) {
                                    $("#studentsTable").DataTable().destroy();
                                }
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

                                // Initialize DataTable
                                $("#studentsTable").DataTable();

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
                    downloadSummary
                };
            }
        });

        instructor.mount('#instructor');
    </script>

@endsection
