@php
    $user = Auth::user();
    $instructor = $user->instructor;

    $departmentName = $instructor?->department?->name ?? 'Not assigned';
    $fleet = $instructor?->fleet;
    $classrooms = $instructor?->classrooms ?? collect();

    $assignment = $fleet
        ? $fleet->car_registration_number
        : ($classrooms->isNotEmpty()
            ? $classrooms->pluck('name')->join(', ')
            : 'Not yet assigned');

    $assignmentIcon = $fleet ? 'fa-car' : 'fa-chalkboard-teacher';

    $attendanceCount = $attendanceCount ?? 0;
    $instructorEstimatedPay = $instructorEstimatedPay ?? 0;
@endphp

<div class="row">

    {{-- Dashboard quotes --}}
    <div class="col-12">
        @include('/dashboard_partials/quotes')
    </div>

    {{-- Quick actions --}}
    <div class="col-12 mb-4">
        <h5 class="mb-2">Actions</h5>
        <hr>

        <div class="row">

            {{-- Scan for attendance --}}
            <div class="col-md-6 mb-3">
                <a href="{{ url('/scanqrcode') }}"
                   class="text-decoration-none">

                    <div class="block block-rounded block-link-shadow border p-3 h-100">
                        <div class="block-content block-content-full d-flex align-items-center justify-content-between">

                            <i class="fa fa-4x fa-qrcode text-primary"
                               aria-hidden="true"></i>

                            <div class="ms-3 text-end">
                                <p class="font-size-h4 font-w700 mb-0 text-dark">
                                    Scan for Attendance
                                </p>
                                <small class="text-muted">
                                    Record student attendance
                                </small>
                            </div>

                        </div>
                    </div>
                </a>
            </div>

            {{-- Schedule lesson --}}
            <div class="col-md-6 mb-3">
                <a href="{{ url('/schedule-lesson-index') }}"
                   class="text-decoration-none">

                    <div class="block block-rounded block-link-shadow border p-3 h-100">
                        <div class="block-content block-content-full d-flex align-items-center justify-content-between">

                            <i class="fa fa-4x fa-calendar-alt text-success"
                               aria-hidden="true"></i>

                            <div class="ms-3 text-end">
                                <p class="font-size-h4 font-w700 mb-0 text-dark">
                                    Schedule Lesson
                                </p>
                                <small class="text-muted">
                                    Manage lesson schedules
                                </small>
                            </div>

                        </div>
                    </div>
                </a>
            </div>

        </div>
    </div>

    {{-- Instructor summary --}}
    <div class="col-12">
        <h5 class="mb-2">Your Summary</h5>
        <hr>

        <div class="row">

            {{-- Department --}}
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="block block-rounded block-link-shadow border h-100">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">

                        <i class="fa fa-2x fa-building text-primary"
                           aria-hidden="true"></i>

                        <div class="ms-3 text-end">
                            <p class="font-size-h5 font-w900 mb-0 text-uppercase">
                                {{ $departmentName }}
                            </p>
                            <p class="mb-0 text-muted">Department</p>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Assigned fleet or classroom --}}
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="block block-rounded block-link-shadow border h-100">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">

                        <i class="fa fa-2x {{ $assignmentIcon }} text-info"
                           aria-hidden="true"></i>

                        <div class="ms-3 text-end">
                            <p class="font-size-h5 font-w900 mb-0 text-uppercase">
                                {{ $assignment }}
                            </p>
                            <p class="mb-0 text-muted">Assigned</p>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Monthly attendance --}}
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="block block-rounded block-link-shadow border h-100">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">

                        <i class="fa fa-2x fa-user-check text-success"
                           aria-hidden="true"></i>

                        <div class="ms-3 text-end">
                            <p class="font-size-h3 font-w900 mb-0">
                                {{ number_format($attendanceCount) }}
                            </p>
                            <p class="mb-0 text-muted">Lessons This Month</p>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Estimated monthly pay --}}
            <div class="col-md-6 col-xl-3 mb-3">
                <div class="block block-rounded block-link-shadow border h-100">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">

                        <i class="fa fa-2x fa-money-bill-wave text-warning"
                           aria-hidden="true"></i>

                        <div class="ms-3 text-end">
                            <p class="font-size-h5 font-w900 mb-0">
                                K{{ number_format((float) $instructorEstimatedPay, 2) }}
                            </p>
                            <p class="mb-0 text-muted">Estimated Pay This Month</p>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Attendance chart --}}
    <div class="col-12 mt-4">
        <div class="block block-rounded block-bordered">

            <div class="block-header block-header-default">
                <h3 class="block-title">
                    <i class="fa fa-chart-line me-1"></i>
                    Attendance Overview
                </h3>
            </div>

            <div class="block-content">
                <div style="position: relative; height: 350px;">
                    <canvas id="attendancesChart"
                            role="img"
                            aria-label="Instructor attendance over time">
                        Your browser does not support the canvas element.
                    </canvas>
                </div>

                <div id="attendanceChartError"
                     class="alert alert-warning d-none mt-3"
                     role="alert">
                    Unable to load attendance data. Please refresh the page to try again.
                </div>
            </div>

        </div>
    </div>

</div>

@push('scripts')
<script>
    $(function () {
        loadAttendanceChart();
    });

    async function loadAttendanceChart() {
        const canvas = document.getElementById('attendancesChart');
        const errorElement = document.getElementById('attendanceChartError');

        if (!canvas || typeof Chart === 'undefined') {
            showChartError();
            return;
        }

        try {
            const response = await fetch(
                '/instructorSummaryData',
                {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                }
            );

            if (!response.ok) {
                throw new Error(
                    `Attendance request failed: ${response.status}`
                );
            }

            const data = await response.json();

            if (!Array.isArray(data)) {
                throw new Error('Invalid attendance response.');
            }

            const labels = data.map(item => item.date);
            const attendanceCounts = data.map(
                item => Number(item.count) || 0
            );

            const existingChart = Chart.getChart(canvas);

            if (existingChart) {
                existingChart.destroy();
            }

            new Chart(canvas, {
                type: 'line',

                data: {
                    labels: labels,

                    datasets: [{
                        label: 'Your Attendances',
                        data: attendanceCounts,

                        borderColor: 'rgba(255, 159, 64, 0.9)',
                        backgroundColor: 'rgba(255, 159, 64, 0.15)',

                        borderWidth: 3,
                        pointRadius: 3,
                        pointHoverRadius: 5,

                        fill: true,
                        tension: 0.3
                    }]
                },

                options: {
                    responsive: true,
                    maintainAspectRatio: false,

                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },

                    plugins: {
                        legend: {
                            position: 'bottom'
                        },

                        tooltip: {
                            enabled: true
                        }
                    },

                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            },

                            ticks: {
                                maxRotation: 45,
                                minRotation: 0,
                                autoSkip: true
                            }
                        },

                        y: {
                            beginAtZero: true,

                            title: {
                                display: true,
                                text: 'Attendance Records'
                            },

                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });

        } catch (error) {
            console.error('Failed to load attendance chart:', error);
            showChartError();
        }

        function showChartError() {
            if (errorElement) {
                errorElement.classList.remove('d-none');
            }
        }
    }
</script>
@endpush
