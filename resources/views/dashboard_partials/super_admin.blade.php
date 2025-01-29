<div class="row" id="app">
    <div class="col-8">
        <div class="p-3">
            <div class="row block">
                <div class="col-md-12 mb-3">
                    <div class="col-md-12 block-rounded block-bordered p-4 dropdown d-inline-block">
                        <form action="{{ url('/') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            Filter
                            <select class="btn border dropdown-toggle" id="filter" name="filter" onchange="this.form.submit()">
                                <div class="dropdown-menu">
                                    <option class="text-left" value="today">Today</option>
                                    <option class="" value="yesterday">Yesterday</option>
                                    <option class="" value="thisweek">This Week</option>
                                    <option class="" value="thismonth">This Month</option>
                                    <option class="" value="lastmonth">Last Month</option>
                                    <option class="" value="thisyear">This Year</option>
                                    <option class="" value="lastyear">Last Year</option>
                                    <option class="" value="alltime">All Time</option>
                                </div>
                            </select>
                        </form>
                    </div>
                </div>
                <div class="col-md-4 col-xl-4">
                    <div class="block block-rounded block-link-shadow border" href="javascript:void(0)">
                        <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                            <div>
                                <i class="fa fa-2x fa-arrow-up"></i>
                            </div>
                            <div class="ml-3 text-right">
                                <p class="font-size-h3 font-w300 mb-0">
                                    K{{number_format($earningsTotal, 2)}}
                                </p>
                                <p class="mb-0">
                                    Sales
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-xl-4">
                    <div class="block block-rounded block-link-shadow border">
                        <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                            <div>
                                <i class="fa fa-2x fa-arrow-up"></i>
                            </div>
                            <div class="ml-3 text-right">
                                <p class="font-size-h3 font-w900 mb-0">
                                    K{{number_format($invoiceBalances, 2)}}
                                </p>
                                <p class="mb-0">
                                    Balances
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-xl-4">
                    <div class="block block-rounded block-link-shadow border">
                        <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                            <div>
                                <i class="far fa-2x fa-user"></i>
                            </div>
                            <div class="ml-3 text-right">
                                <p class="font-size-h3 font-w900 mb-0">
                                    {{$studentCount}}
                                </p>
                                <p class="mb-0">
                                    Students
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-xl-4">
                    <div class="block block-rounded block-link-shadow border">
                        <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                            <div>
                                <i class="fa fa-2x fa-chart-line"></i>
                            </div>
                            <div class="mr-3">
                                <p class="font-size-h3 font-w900 mb-0">
                                    K{{number_format($expensesTotal, 2)}}
                                </p>
                                <p class="mb-0">
                                    Expenses
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-xl-4">
                    <div class="block block-rounded block-link-shadow border">
                        <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                            <div>
                                <i class="far fa-2x fa-clock"></i>
                            </div>
                            <div class="ml-3 text-right">
                                <p class="font-size-h3 font-w900 mb-0">
                                    {{ $attendanceCount }}
                                </p>
                                <p class="mb-0">
                                    Attendanes
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row block">
                <div class="col-8">
                    <div class="block-conent block-rounded block-bordered">
                        <canvas id="attendancesChart" height="200"></canvas>
                    </div>
                </div>
                <div class="col-4">
                    <div class="block-conent block-rounded block-bordered">
                        <canvas id="coursesChart" width="400" height="400"></canvas>
                    </div>
                </div>
            </div>

            <div class="row mt-2 p-0">
                <div class="col-sm-12 col-md-12 p-0">
                    <div class="block block-rounded block-bordered block-mode-loading-refresh mb-0">
                    <div class="block-header border-bottom">
                        <h3 class="block-title">Students</h3>
                    </div>
                    <div class="block-content" style="height: 30em !important; overflow-y:scroll">
                        <div class="table-responsive">
                        <table class="table table-striped table-hover table-borderless table-vcenter fs-sm">
                            <thead>
                            <tr class="text-uppercase">
                                <th class="fw-bold text-center" style="width: 120px;">Photo</th>
                                <th class="fw-bold">Name</th>
                                <th class="fw-bold">Sex</th>
                                <th class="d-none d-sm-table-cell fw-bold text-center">Course</th>
                                <th class="fw-bold text-center" style="width: 60px;">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($student as $student)
                            <tr>
                                <td class="text-center">
                                <img class="img-avatar img-avatar32 img-avatar-thumb" src="media/avatars/avatar2.jpg" alt="">
                                </td>
                                <td>
                                <div class="fw-semibold fs-base">{{$student->fname}} {{$student->sname}}</div>
                                <div class="text-muted">
                                    @if(isset($student->user->email))

                                        {{$student->user->email}}

                                    @else

                                    @endif
                                </div>
                                </td>
                                <td>{{$student->gender}}</td>
                                <td class="d-none d-sm-table-cell fs-base text-center">

                                    @if(isset($student->course->name))
                                    <span class="badge bg-dark">

                                    {{$student->course->name}}
                                        <div class="text-muted">{{$student->course->duration}} days</div>
                                    </span>

                                    @else
                                    <a href="">
                                        <span class="badge bg-danger">
                                        Not enrolled yet
                                        </span>
                                    </a>
                                    @endif
                                </td>
                                <td class="text-center">
                                <a href="{{ url('/viewstudent', $student->id) }}" data-bs-toggle="tooltip" data-bs-placement="left" title="" class="js-bs-tooltip-enabled" data-bs-original-title="View Colleague">
                                    <i class="fa fa-fw fa-user-circle"></i>
                                </a>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                    </div>
                </div>

                <!-- Invoices snipest -->
                <div class="col-sm-12 col-md-12 p-0 mt-4">
                    <div class="block block-rounded block-bordered block-mode-loading-refresh mb-0">
                    <div class="block-header border-bottom">
                        <h3 class="block-title">Invoices</h3>
                    </div>
                        <div class="block-content" id="invoices" style="height: 30em !important; overflow-y:scroll">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-borderless table-vcenter fs-sm">
                                    <thead>
                                    <tr class="text-uppercase">
                                        <th class="fw-bold" style="min-width: 200px;">Invoice No</th>
                                        <th class="d-none d-sm-table-cell fw-bold" style="min-width: 200px;">Date</th>
                                        <th class="fw-bold" style="min-width: 200px;">Student</th>
                                        <th class="fw-bold">Balance</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                            <tr v-for="item in info" :key="item.id">
                                                <td><a :href="'view-invoice/' + item.id">@{{ item.invoice_number }}</td>
                                                <td>@{{timeCreated(item.date_created)}}</td>
                                                <td class="text-uppercase">@{{ item.student.fname }} @{{ item.student.mname }} @{{ item.student.sname }}</td>
                                                <td>K@{{ formatPrice(item.invoice_balance) }}</td>
                                            </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4 p-0">
                <div class="col-sm-12 col-md-12 p-0">
                    <div class="block block-rounded block-bordered block-mode-loading-refresh mb-0">
                    <div class="block-header border-bottom">
                        <h3 class="block-title">System activities</h3>
                    </div>
                    <div class="block-content h-100">
                        <div class="table-responsive">
                        <table class="table table-striped table-hover table-borderless table-vcenter fs-sm">
                            <thead>
                            <tr class="text-uppercase">
                                <th class="fw-bold">Activity</th>
                                <th class="fw-bold">By</th>
                                <th class="fw-bold">Date time</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($activities as $activity)
                                <tr>
                                    <td>{{ $activity->description }}</td>
                                    <td>
                                        {{ $activity->causer->administrator?->fname ?? $activity->causer->instructor?->fname ?? 'System' }}
                                        {{ $activity->causer->administrator?->sname ?? $activity->causer->instructor?->sname ?? 'System' }}
                                    </td>
                                    <td>{{ $activity->updated_at->format('j F, Y - H:m:s') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        {{ $activities->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="p-3">
            <div class="row block">
                @php
                    use Carbon\Carbon;

                    // Fetch instructors with attendance counts for the current month
                    $instructorss = \App\Models\Instructor::where('status', 'active')
                        ->withCount(['attendances as total_attendances' => function ($query) {
                            $query->whereMonth('created_at', Carbon::now()->month)
                                ->whereYear('created_at', Carbon::now()->year);
                        }])
                        ->get();

                    // Calculate the total attendances across all instructors
                    $totalAttendancesAllInstructors = $instructorss->sum('total_attendances');

                    $thisMonth = Carbon::now()->format('F');
                @endphp
                <div class="col-12">
                    <div class="block-header border-bottom">
                        <h3 class="block-title p-0">
                            <strong class="">Instructor perfomance</strong>
                            <div class="sm-text text-uppercase">{{ $thisMonth }}</div>
                        </h3>
                    </div>
                    <div class="block-content p-0" style="height: 17em; overflow-x: scroll">
                        <table class="table table-responsive">
                            <thead>
                                <th style="min-width: 170px">Instructor</th>
                                <th class="text-center" style="min-width: 200px">Attendance count</th>
                                <th class="text-center" style="min-width: 150px">Attendance %</th>
                                <th class="text-end" style="min-width: 170px">Expected bonus</th>
                            </thead>
                            <tbody>
                                @foreach($instructors as $instructor)
                                    <tr>
                                        <td>{{ $instructor->fname }} {{ $instructor->sname }}</td>
                                        <td class="text-center">{{ $instructor->attendances->count() }}</td>
                                        <td class="text-center">
                                            @if ($totalAttendancesAllInstructors > 0)
                                                {{ number_format(($instructor->attendances->count() / $totalAttendancesAllInstructors) * 100, 2) }}%
                                            @else
                                                0.00%
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            K{{ number_format($instructor->attendances->count() * $settings->bonus), 2 }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="block-footer pt-3 pb-3">
                        <p>
                            Bonus is paged at <b>K{{ number_format($settings->bonus, 2) }}</b> per attendance. <i><a href="/settings/#bonus">Edit</a></i>
                        </p>
                        <p class="text-warning">
                            System will automatically pay bonuses on 28th
                        </p>
                        <p>
                           <a href="#" class="btn btn-primary">Pay early</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
    const { createApp } = Vue

    createApp({
        data() {
        return {
            count: 0,
            info: [],
        }
        },
        methods : {

            async read() {
                const { data } = window.axios.get('/api/invoicesHome');
                // console.log(data)
            },
            // Creating function
            timeCreated: function(date){
                return moment(date).format('DD MMMM, YYYY');
            },

            formatPrice(value) {
                let val = (value/1).toFixed(2).replace(',', '.')
                return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
            },

            view_invoice: function(invoice_number){
                const url = `api/invoice-view/Daron-2022-2`;
                axios.get(url)
                    .then((response) => {
                        res(this.invoice = response.data);
                    })
                    .catch((err) => {
                        rej(err);
                    });
            },

            invoice_edit: function(invoice_number){

            },

            invoice_delete: function(invoice_number){

            },
        },

        mounted () {
            axios
            .get('api/invoices')
            .then(response => (this.info = response.data))
        }

    }).mount('#invoices')
</script>

<script>
    document.getElementById("filter").value = "{{ $time }}"
</script>
<script>
    const ctx = document.getElementById('attendancesChart');
    $(function() {
        getXlsxData();
      });

      function getXlsxData() {
        var xlsxUrl =
          "/summaryData"
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
              label: 'Attendances ',
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

        var ctx1 = document.getElementById('coursesChart');

        var coursesChart = new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['Full Course B', 'Full Course C1', 'Full Course B VIP', '20 Days Course B'], // Data labels
                datasets: [{
                    label: 'My Dataset',
                    data: [100, 20, 10, 50], // Data values
                    backgroundColor: ['rgba(255, 99, 132, 0.9)', 'rgba(54, 162, 235, 0.9)', 'rgba(255, 206, 86, 0.9)', 'rgba(255, 116, 86, 0.9)'], // Segment colors
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw;
                            }
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 20,
                        bottom: 20
                    }
                }
            }
        });
    }
  </script>
