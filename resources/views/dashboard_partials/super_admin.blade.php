<div class="row" id="app">
    <div class="row block">
        <div class="col-md-12 mb-3">
            <div class="col-md-12 block-rounded block-bordered p-4 dropdown d-inline-block">
                <form action="{{ url('/') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    Filter
                    <select class="btn btn-primary dropdown-toggle" id="filter" name="filter" onchange="this.form.submit()">
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
            <div class="block block-rounded block-link-shadow bg-primary" href="javascript:void(0)">
                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                    <div>
                        <i class="fa fa-2x fa-arrow-alt-circle-up text-primary-lighter"></i>
                    </div>
                    <div class="ml-3 text-right">
                        <p class="text-white font-size-h3 font-w300 mb-0">
                            K{{number_format($earningsTotal, 2)}}
                        </p>
                        <p class="text-white-75 mb-0">
                            Total Sales
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xl-4">
            <div class="block block-rounded block-link-shadow bg-warning">
                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                    <div>
                        <i class="fa fa-2x fa-arrow-alt-circle-up text-success-light"></i>
                    </div>
                    <div class="ml-3 text-right">
                        <p class="text-white font-size-h3 font-w300 mb-0">
                            K{{number_format($invoiceBalances, 2)}}
                        </p>
                        <p class="text-white-75 mb-0">
                            Balances
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xl-4">
            <div class="block block-rounded block-link-shadow bg-success">
                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                    <div>
                        <i class="far fa-2x fa-user text-success-light"></i>
                    </div>
                    <div class="ml-3 text-right">
                        <p class="text-white font-size-h3 font-w300 mb-0">
                            {{$studentCount}}
                        </p>
                        <p class="text-white-75 mb-0">
                            Students
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <a href="/expenses" class="col-md-4 col-xl-4">
            <div class="block block-rounded block-link-shadow bg-danger">
                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                    <div>
                        <i class="fa fa-2x fa-chart-line text-black-50"></i>
                    </div>
                    <div class="mr-3">
                        <p class="text-white font-size-h3 font-w300 mb-0">
                            K{{number_format($expensesTotal, 2)}}
                        </p>
                        <p class="text-white-75 mb-0">
                            Expenses
                        </p>
                    </div>
                </div>
            </div>
        </a>
        <div class="col-md-4 col-xl-4">
            <div class="block block-rounded block-link-shadow bg-info">
                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                    <div>
                        <i class="far fa-2x fa-clock text-success-light"></i>
                    </div>
                    <div class="ml-3 text-right">
                        <p class="text-white font-size-h3 font-w300 mb-0">
                            {{ $attendanceCount }}
                        </p>
                        <p class="text-white-75 mb-0">
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
        <div class="col-sm-6 col-md-6 p-0">
            <div class="block block-rounded block-bordered block-mode-loading-refresh mb-0">
              <div class="block-header border-bottom">
                <h3 class="block-title">Students</h3>
                <div class="block-options">
                  <button type="button" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                    <i class="fa fa-sync"></i>
                  </button>
                  <button type="button" class="btn-block-option">
                    <i class="fa fa-wrench"></i>
                  </button>
                </div>
              </div>
              <div class="block-content">
                <div class="table-responsive h-100">
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
        <div class="col-sm-6 col-md-6 p-0" id="invoices">
            <div class="block block-rounded block-bordered block-mode-loading-refresh h-100 mb-0">
              <div class="block-header border-bottom">
                <h3 class="block-title">Invoices</h3>
                <div class="block-options">
                  <button type="button" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                    <i class="fa fa-sync"></i>
                  </button>
                  <button type="button" class="btn-block-option">
                    <i class="fa fa-wrench"></i>
                  </button>
                </div>
              </div>
                <div class="block-content">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-borderless table-vcenter fs-sm">
                            <thead>
                            <tr class="text-uppercase">
                                <th class="fw-bold">Invoice No</th>
                                <th class="d-none d-sm-table-cell fw-bold">Date</th>
                                <th class="fw-bold">Student</th>
                                <th class="fw-bold">Balance</th>
                            </tr>
                            </thead>
                            <tbody>
                                <ul v-if="info.length">
                                    <tr v-for="item in info" :key="item.id">
                                        <td><a :href="'view-invoice/' + item.invoice_number">@{{ item.invoice_number }}</td>
                                        <td>@{{timeCreated(item.date_created)}}</td>
                                        <td class="text-uppercase">@{{ item.student.fname }} @{{ item.student.mname }} @{{ item.student.sname }}</td>
                                        <td>K@{{ formatPrice(item.invoice_balance) }}</td>
                                    </tr>
                                </ul>
                            </tbody>
                        </table>
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
                    data: [10, 20, 30, 50], // Data values
                    backgroundColor: ['rgba(255, 99, 132, 0.9)', 'rgba(54, 162, 235, 0.9)', 'rgba(255, 206, 86, 0.9)'], // Segment colors
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
