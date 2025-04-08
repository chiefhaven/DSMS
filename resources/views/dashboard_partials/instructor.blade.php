<div class="row">
        <div class="col-md-12">
            <div class="row">
                @include('/dashboard_partials/quotes')

                <div class="col-md-12 col-xl-12 mb-4">
                    <h5>Actions</h5>
                    <hr>
                    <div class="block block-content">
                        <div class="row">
                            <div class="block block-content">
                                <div class="row">
                                    <!-- Scan for Attendance -->
                                    <div class="col-md-6">
                                        <a href="{{ url('/scanqrcode') }}" class="text-decoration-none">
                                            <div class="block block-rounded block-link-shadow border p-3 hover-effect">
                                                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <i class="fa fa-4x fa-qrcode text-primary"></i>
                                                    </div>
                                                    <div class="ms-3 text-end">
                                                        <p class="font-size-h4 font-w700 mb-0 text-dark">Scan for Attendance</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>

                                    <!-- Schedule Attendance -->
                                    <div class="col-md-6">
                                        <a href="{{ url('/schedule-lesson-index') }}" class="text-decoration-none">
                                            <div class="block block-rounded block-link-shadow border p-3 hover-effect">
                                                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <i class="fa fa-4x fa-calendar-alt text-success"></i>
                                                    </div>
                                                    <div class="ms-3 text-end">
                                                        <p class="font-size-h4 font-w700 mb-0 text-dark">Schedule lesson</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <h5>Your Summary</h5>
                        <hr>

                        <div class="row">
                            <!-- Department -->
                            <div class="col-md-4">
                                <div class="block block-rounded block-link-shadow border">
                                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="fa fa-2x fa-building"></i>
                                        </div>
                                        <div class="ms-3 text-right">
                                            <p class="font-size-h2 font-w900 mb-0 text-uppercase">
                                                {{ Auth::user()->instructor->department->name ?? '' }}
                                            </p>
                                            <p class="mb-0">Department</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Assigned Fleet or Classroom -->
                            <div class="col-md-4">
                                <div class="block block-rounded block-link-shadow border">
                                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                                        <div>
                                            @if (Auth::user()->instructor->fleet)
                                                <i class="fa fa-2x fa-car"></i>
                                            @else
                                                <i class="fa fa-2x fa-chalkboard-teacher"></i>
                                            @endif
                                        </div>
                                        <div class="ms-3 text-right">
                                            <p class="font-size-h3 font-w900 mb-0 text-uppercase">
                                                @if (Auth::user()->instructor->fleet)
                                                    {{ Auth::user()->instructor->fleet->car_registration_number }}
                                                @elseif (Auth::user()->instructor->classrooms && Auth::user()->instructor->classrooms->isNotEmpty())
                                                    {{ Auth::user()->instructor->classrooms->pluck('name')->join(', ') }}
                                                @else
                                                    Not yet assigned car or classroom
                                                @endif
                                            </p>
                                            <p class="mb-0">Assigned</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Today's Attendance -->
                            <div class="col-md-4">
                                <div class="block block-rounded block-link-shadow border">
                                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="fa fa-2x fa-user-check"></i>
                                        </div>
                                        <div class="ms-3 text-right">
                                            <p class="font-size-h3 font-w900 mb-0 text-uppercase">
                                                {{ $attendanceCount }}
                                            </p>
                                            <p class="mb-0">Today's Attendances</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Expected Pay -->
                            <div class="col-md-4">
                                <div class="block block-rounded block-link-shadow border">
                                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                                        <div>
                                            <i class="fa fa-2x fa-money-bill-wave"></i>
                                        </div>
                                        <div class="ms-3 text-right">
                                            <p class="font-size-h3 font-w900 mb-0 text-uppercase">
                                                K{{ number_format($attendanceCount * $settings->bonus, 2) }}
                                            </p>
                                            <p class="mb-0">This month's expected pay</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- End Row -->
                    </div>
                </div>

                <div class="col-sm-12 mt-5">
                    <div class="block ">
                        <div class="block-conent block-rounded block-bordered">
                            <canvas id="attendancesChart" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>

        </div>

</div>

<script>
    const ctx = document.getElementById('attendancesChart');
    $(function() {
        getXlsxData();
      });

      function getXlsxData() {
        var xlsxUrl =
          "/instructorSummaryData"
        var xlsxData = $.getJSON(xlsxUrl, function(data) {
          $.each(data, function(i, el) {
            labels.push(el.date);
            Attendances.push(el.count);
          });
          load_chart();
        });
      }
      var labels = [],
        Attendances = []

      function load_chart() {
        var attendancesChart = new Chart('attendancesChart', {
          type: 'line',
          data: {
            labels: labels,
            datasets: [{
              label: 'Your Attendances ',
              fill: false,
              data: Attendances,
              backgroundColor: 'rgb(255, 159, 64)',
              borderColor: 'rgb(255, 159, 64, 0.8)',
              borderWidth: 3,
              radius: 0,
            }, ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
              position: 'bottom',
            },
            layout: {
              padding: {
                top: 35,
                right: 15,
              }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        parser: 'YYYY-MM-DD', // Ensure this matches your data format
                        unit: 'day', // Use 'day' for daily data
                        displayFormats: {
                            day: 'D MMM' // Format for daily tick marks
                        },
                        tooltipFormat: 'D MMM YYYY' // Tooltip format
                    },
                    ticks: {
                        source: 'data', // Ensures ticks are based on data
                        autoSkip: false, // Disable automatic skipping
                        stepSize: 1, // Display every day (adjust if necessary)
                        maxRotation: 60, // Prevent tick label rotation
                        minRotation: 60
                    },
                    title: {
                        display: true,
                        text: 'Date' // Optional: Add title for the x-axis
                    }
                },
                y: {
                    beginAtZero: true
                }
            }
          }
        });
    }
</script>
