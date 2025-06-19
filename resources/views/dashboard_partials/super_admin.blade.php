<div class="row" id="app">

        @include('/dashboard_partials/quotes')

    <div class="col-md-8">
        <div class="p-3">

            @include('/dashboard_partials/scan_to_pay')

            <div class="row block" id="dashboardSummary" v-cloak>
                <div v-if="isLoading" class="text-center py-7">
                    <span class="spinner-border text-primary" role="status"></span>
                    <p>Loading summary</p>
                </div>

                <div v-else class="row">
                    <div class="col-md-12 mb-3">
                        <div class="col-md-12 block-rounded block-bordered p-4 d-inline-block">
                            <form @submit.prevent class="row g-2 align-items-end">
                                @csrf

                                <div class="col-auto d-flex align-items-center gap-2">
                                    <label for="filter" class="form-label mb-0 fw-semibold">Filter</label>
                                    <select
                                      id="filter"
                                      v-model="filter"
                                      name="filter"
                                      class="form-select px-3 py-2 shadow-sm border rounded-pill"
                                      @change="onFilterChange"
                                    >
                                      <option value="today">Today</option>
                                      <option value="yesterday">Yesterday</option>
                                      <option value="thisweek">This Week</option>
                                      <option value="thismonth">This Month</option>
                                      <option value="lastmonth">Last Month</option>
                                      <option value="thisyear">This Year</option>
                                      <option value="lastyear">Last Year</option>
                                      <option value="alltime">All Time</option>
                                      <option value="custom">Custom</option>
                                    </select>
                                </div>

                                <template v-if="filter === 'custom'">
                                    <div class="col-auto">
                                        <label for="startDate" class="form-label mb-1">Start Date</label>
                                        <input type="text" id="startDate"
                                               class="form-control form-control rounded-0"
                                               v-model="startDate"
                                               @change="onCustomDateChange"
                                               placeholder="YYYY-MM-DD" />
                                    </div>

                                    <div class="col-auto">
                                        <label for="endDate" class="form-label mb-1">End Date</label>
                                        <input type="text" id="endDate"
                                               class="form-control form-control rounded-0"
                                               v-model="endDate"
                                               @change="onCustomDateChange"
                                               placeholder="YYYY-MM-DD" />
                                    </div>
                                </template>
                            </form>

                        </div>
                    </div>

                    <!-- Dashboard cards (Sales, Balances, Students, etc.) -->
                    <div class="col-md-4 col-xl-4 mb-4" v-for="(card, index) in summaryCards" :key="index">
                        <div class="block block-rounded block-link-shadow border shadow-sm h-100">
                          <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                            <div class=" ">
                              <i :class="`fa fa-2x ${card.icon}`"></i>
                            </div>
                            <div class="ms-3 text-end">
                              <p class="fs-3 fw-semibold mb-0" v-if="card.currency">K@{{ formatCurrency(card.value) }}</p>
                              <p class="fs-3 fw-semibold mb-0" v-else>@{{ card.value }}</p>
                              <p class="mb-0 text-muted">@{{ card.label }}</p>
                            </div>
                          </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row block">
                <div class="col-12">
                    <div class="block-header border-bottom">
                        <h3 class="block-title text-primary"><i class="fa fa-chart-bar me-2"></i>Attendance Statistics</h3>
                    </div>
                    <div class="block-content p-0">
                        <canvas id="attendancesChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <div class="row mt-2 p-0 g-3">
                <!-- Students Table -->
                <div class="col-sm-12 col-md-12 p-0">
                    <div class="block block-rounded block-bordered block-mode-loading-refresh mb-0 shadow-sm">
                        <div class="block-header border-bottom bg-body-light">
                            <h3 class="block-title text-primary"><i class="far fa-user me-2"></i>Students</h3>
                            <div class="block-options">
                                <a href="/students" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                                    More students...
                                </a>
                            </div>
                        </div>
                        <div class="block-content" style="height: 30em; overflow-y: auto">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-vcenter mb-0 fs-sm">
                                    <thead>
                                        <tr class="text-uppercase">
                                            <th class="fw-bold text-center" style="width: 100px;">Photo</th>
                                            <th class="fw-bold">Name</th>
                                            <th class="fw-bold">Gender</th>
                                            <th class="d-none d-sm-table-cell fw-bold text-center">Course</th>
                                            <th class="fw-bold text-center" style="width: 80px;">View</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($student as $student)
                                        <tr>
                                            <td class="text-center">
                                                <img class="img-avatar img-avatar32 img-avatar-thumb" src="media/avatars/avatar2.jpg" alt="">
                                            </td>
                                            <td>
                                                <div class="fw-semibold fs-base text-dark">{{$student->fname}} {{$student->sname}}</div>
                                                <div class="text-muted small">
                                                    @if(isset($student->user->email))
                                                        {{$student->user->email}}
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $student->gender == 'Male' ? 'primary' : 'pink' }}">
                                                    {{$student->gender}}
                                                </span>
                                            </td>
                                            <td class="d-none d-sm-table-cell text-center">
                                                @if(isset($student->course->name))
                                                    <b>{{$student->course->name}}</b>
                                                    <div class="text-muted small">{{$student->course->duration}} days</div>
                                                @else
                                                <a href="#">
                                                    <span class="badge bg-danger">
                                                        Not enrolled
                                                    </span>
                                                </a>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ url('/viewstudent', $student->id) }}"
                                                   class="btn btn-sm btn-outline-primary js-bs-tooltip-enabled"
                                                   data-bs-toggle="tooltip"
                                                   data-bs-placement="left"
                                                   title="View Student">
                                                    <i class="fa fa-eye"></i>
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
            </div>

            <div class="row mt-2 p-0 g-3">
                <!-- Invoices Table -->
                <div class="col-sm-12 col-md-12 p-0">
                    <div class="block block-rounded block-bordered block-mode-loading-refresh mb-0 shadow-sm">
                        <div class="block-header border-bottom bg-body-light">
                            <h3 class="block-title text-success">
                                <i class="fa fa-file-invoice me-2"></i>Invoices
                            </h3>
                        </div>

                        <div class="block-content" id="invoices" style="height: 30em; overflow-y: auto">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-vcenter mb-0 fs-sm">
                                    <thead class="bg-success-lighter">
                                        <tr class="text-uppercase">
                                            <th class="fw-bold">Invoice No.</th>
                                            <th class="d-none d-sm-table-cell fw-bold">Date</th>
                                            <th class="fw-bold">Student</th>
                                            <th class="fw-bold text-end">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in info" :key="item.id">
                                            <td>
                                                <a :href="'view-invoice/' + item.id" class="fw-semibold text-dark">
                                                    @{{ item.invoice_number }}
                                                </a>
                                            </td>
                                            <td class="d-none d-sm-table-cell">
                                                <span class="text-muted">@{{ timeCreated(item.date_created) }}</span>
                                            </td>
                                            <td>
                                                @{{ item.student.fname }} @{{ item.student.mname }} <div class="fw-semibold text-dark"> @{{ item.student.sname }} </div>
                                            </td>
                                            <td class="text-end">
                                                <span class="fw-bold" :class="{'text-danger': item.invoice_balance > 0, 'text-success': item.invoice_balance == 0}">
                                                    K@{{ formatPrice(item.invoice_balance) }}
                                                </span>
                                            </td>
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
                    <div class="block block-rounded block-bordered block-mode-loading-refresh mb-0 shadow-sm">
                        <div class="block-header border-bottom bg-body-light">
                            <h3 class="block-title text-info">
                                <i class="far fa-list-alt me-2"></i>System Activities
                            </h3>
                        </div>
                        <div class="block-content h-100" style="max-height: 500px; overflow-y: auto;">
                            <div class="table-responsive">
                                <table id="activitiesTable" class="table table-striped table-hover table-vcenter mb-0 fs-sm">
                                    <thead class="bg-info-lighter">
                                        <tr class="text-uppercase">
                                            <th class="fw-bold" style="width: 60%;">Activity</th>
                                            <th class="fw-bold" style="width: 20%;">Performed By</th>
                                            <th class="fw-bold text-end" style="width: 20%;">Date & Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($activities as $activity)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span>{{ $activity->description }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span>
                                                        {{ $activity->causer->administrator?->fname ?? $activity->causer->instructor?->fname ?? 'System' }}
                                                        {{ $activity->causer->administrator?->sname ?? $activity->causer->instructor?->sname ?? '' }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <span class="text-muted" data-bs-toggle="tooltip" data-bs-placement="left" title="{{ $activity->updated_at->diffForHumans() }}">
                                                    {{ $activity->updated_at->format('j M, Y - H:i:s') }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if($activities->isEmpty())
                            <div class="text-center py-4">
                                <i class="far fa-clipboard-list fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No activities found</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
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
                        <div id="bonuses">
                            <button class="btn btn-primary rounded-pill px-4" :disabled="paymentLoading" @click="payEarly">
                                <span v-if="paymentLoading">
                                    <i class="fa fa-spinner fa-spin"></i> Processing...
                                </span>
                                <span v-else>
                                    Pay early
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

        const bonuses = createApp({
            setup() {
                const paymentLoading = ref(false)

                const payEarly = () => {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You are about to process an early payment.",
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Proceed',
                        confirmButtonColor: '#28a745',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            NProgress.start();
                            paymentLoading.value = true;
                            axios.post('/api/bonuses/pay-early')
                                .then(response => {
                                    Swal.fire('Success!', 'Early payment processed.', 'success');
                                    // Optional: reload bonuses or update UI
                                })
                                .catch(error => {
                                    const message = error.response?.data?.message || 'An unexpected error occurred.';
                                    Swal.fire('Payment not processed!', message, 'error');
                                })
                                .finally(() => {
                                    NProgress.done();
                                    paymentLoading.value = false;
                                });
                        }
                    });
                };

                return {
                    payEarly,
                    paymentLoading,

                };
            }
        }).mount('#bonuses');


        const invoices = createApp({
            setup() {
                const count = ref(0);
                const info = ref([]);
                const invoice = ref(null);

                // Fetch invoices on page load
                onMounted(() => {
                    axios.get('/api/invoices')
                        .then(response => {
                            info.value = response.data;
                        })
                        .catch(error => {
                        });
                });

                const getStatusColor = async(status) => {
                    const statusMap = {
                        'paid': 'success',
                        'pending': 'warning',
                        'overdue': 'danger',
                        'partial': 'info'
                    };
                    return statusMap[status.toLowerCase()] || 'secondary';
                };

                const getPaymentProgress = async (item) => {
                    if (item.invoice_total === 0) return 0;
                    const paid = item.invoice_total - item.invoice_balance;
                    return Math.round((paid / item.invoice_total) * 100);
                };

                const getProgressBarClass = async(item) => {
                    const progress = this.getPaymentProgress(item);
                    if (progress === 100) return 'bg-success';
                    if (progress > 50) return 'bg-info';
                    if (progress > 0) return 'bg-warning';
                    return 'bg-danger';
                };

                const filterInvoices = async() => {
                    // Implement your filtering logic here
                };

                // Fetch single invoice view
                const view_invoice = (invoice_number) => {
                    const url = `/api/invoice-view/${invoice_number}`;
                    axios.get(url)
                        .then(response => {
                            invoice.value = response.data;
                        })
                        .catch(err => {
                        });
                };

                // Format date using moment.js
                const timeCreated = (date) => {
                    return moment(date).format('DD MMMM, YYYY');
                };

                // Format currency
                const formatPrice = (value) => {
                    let val = (value / 1).toFixed(2).replace(',', '.');
                    return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                };

                return {
                    count,
                    info,
                    invoice,
                    view_invoice,
                    timeCreated,
                    formatPrice,
                    getStatusColor,
                    getProgressBarClass,
                    getPaymentProgress
                };
            }
        }).mount('#invoices');

        const dashboardSummary = createApp({
            setup() {
                const filter = ref('today')
                const startDate = ref()
                const endDate = ref()
                const isLoading = ref(false)

                const summaryInfo = ref({
                    earningsTotal: 0,
                    invoiceBalances: 0,
                    studentCount: 0,
                    expensesTotal: 0,
                    expensesPayments: 0,
                    attendanceCount: 0,
                });

                const summaryCards = computed(() => [
                    { icon: 'fa-arrow-up', value: summaryInfo.value.earningsTotal, label: 'Sales', currency: true },
                    { icon: 'fa-wallet', value: summaryInfo.value.invoiceBalances, label: 'Balances', currency: true },
                    { icon: 'fa-user', value: summaryInfo.value.studentCount, label: 'Students', currency: false },
                    { icon: 'fa-chart-line', value: summaryInfo.value.expensesPayments, label: 'Expenses paid', currency: true },
                    { icon: 'fa-chart-bar', value: summaryInfo.value.expensesTotal, label: 'Expenses posted', currency: true },
                    { icon: 'fa-clock', value: summaryInfo.value.attendanceCount, label: 'Attendances', currency: false },
                ]);


                // Initialize datepickers on mount
                onMounted(() => {
                    isLoading.value = true;

                    filterDashboard();
                });

                const filterDashboard = async () => {
                    NProgress.start()
                    try {
                        const response = await axios.get('/api/dashboardSummary', {
                            params: {
                              filter: filter.value,
                              ...(filter.value === 'custom' && {
                                start_date: startDate.value,
                                end_date: endDate.value,
                              }),
                            },
                        });

                        summaryInfo.value = response.data;
                        console.log(response.data);
                    } catch (error) {
                        const errorData = error.response.data;

                        if (errorData.errors && typeof errorData.errors === 'object') {
                            Object.values(errorData.errors).forEach(errorArray => {
                                errorArray.forEach(msg => {
                                    showAlert('', msg, { icon: 'error' });
                                });
                            });
                        } else {
                            showAlert('', errorData.message || errorData, { icon: 'error' });
                        }

                    }finally{
                        NProgress.done();
                        isLoading.value = false;

                    }
                }

                watch(
                    () => filter.value,
                    (newVal) => {
                        if (newVal === 'custom') {
                            nextTick(() => {
                                const today = new Date();
                                const day = String(today.getDate()).padStart(2, '0');
                                const month = String(today.getMonth() + 1).padStart(2, '0');
                                const year = today.getFullYear();

                                // Set the date in yyyy-mm-dd format (adjusted for consistency)
                                const formattedDate = `${year}-${month}-${day}`;

                                $('#startDate').datepicker({
                                    format: 'yyyy-mm-dd',
                                    autoclose: true,
                                    todayHighlight: true,
                                }).on('changeDate', function (e) {
                                    startDate.value = e.format('yyyy-mm-dd'); // Ensure correct format
                                    onCustomDateChange();
                                }).datepicker('setDate', formattedDate);

                                $('#endDate').datepicker({
                                    format: 'yyyy-mm-dd',
                                    autoclose: true,
                                    todayHighlight: true,
                                }).on('changeDate', function (e) {
                                    endDate.value = e.format('yyyy-mm-dd'); // Ensure correct format
                                    onCustomDateChange();
                                }).datepicker('setDate', formattedDate);
                            });

                        }
                    },
                    { immediate: true }
                );


                const onFilterChange = () => {
                    if (filter.value !== 'custom') {
                        filterDashboard();
                    }
                };

                const onCustomDateChange = () => {
                    if (filter.value === 'custom' && startDate.value && endDate.value) {
                        filterDashboard();
                    }
                };

                // Format currency
                const formatCurrency = (value) => {
                    let val = (value / 1).toFixed(2).replace(',', '.');
                    return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
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
                        showConfirmButton: false,
                        didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer);
                        toast.addEventListener('mouseleave', Swal.resumeTimer);
                        }
                    };

                    if (message) baseOptions.title = message;
                    if (detail) baseOptions.text = detail;

                    return Swal.fire(baseOptions);
                };

                return {
                    summaryInfo,
                    filter,
                    startDate,
                    endDate,
                    filterDashboard,
                    formatCurrency,
                    onFilterChange,
                    onCustomDateChange,
                    isLoading,
                    summaryCards,

                };
            }
        }).mount('#dashboardSummary');
    </script>

<script>

    $(document).ready(function () {
        $.extend($.fn.dataTable.ext.type.order, {
            "custom-date-pre": function (data) {
                // Handle "9 May, 2024, 02:00:00" format
                const parts = data.trim().split(/[\s,]+/);
                if (parts.length < 4) return 0;

                const day = parseInt(parts[0], 10);
                const monthNames = [
                    "January", "February", "March", "April", "May", "June",
                    "July", "August", "September", "October", "November", "December"
                ];
                const month = monthNames.indexOf(parts[1]);
                const year = parseInt(parts[2], 10);
                const timeParts = parts[3].split(':');
                const hours = parseInt(timeParts[0], 10);
                const minutes = parseInt(timeParts[1], 10);
                const seconds = timeParts.length > 2 ? parseInt(timeParts[2], 10) : 0;

                return new Date(year, month, day, hours, minutes, seconds).getTime();
            }
        });

        $('#activitiesTable').DataTable({
            order: [[1, 'desc']], // Sort by Date column
            columnDefs: [
                { targets: 1, type: 'custom-date' }, // Apply custom date sorting
                { targets: 0, orderable: false },
            ]
        });
    });

</script>
<script>
    const ctx = document.getElementById('attendancesChart');
    $(function() {
        getXlsxData();
      });
      function getXlsxData() {
        var xlsxUrl = "/summaryData";

        $.getJSON(xlsxUrl, function(data) {

            var labelsSet = new Set();
            var attendancesMap = {};
            var schedulesMap = {};

            // Ensure data exists
            if (!data || !data.attendances || !data.schedules) {
                return;
            }

            // Process attendances
            data.attendances.forEach(el => {
                if (el.date) {
                    labelsSet.add(el.date);
                    attendancesMap[el.date] = el.count || 0;
                }
            });

            // Process schedules
            data.schedules.forEach(el => {
                if (el.date) {
                    labelsSet.add(el.date);
                    schedulesMap[el.date] = el.count || 0;
                }
            });

            // Convert Set to sorted array
            var labels = Array.from(labelsSet).sort();

            // Create final arrays ensuring all dates exist
            var attendances = labels.map(date => attendancesMap[date] || 0);
            var schedules = labels.map(date => schedulesMap[date] || 0);

            load_chart(labels, attendances, schedules);
        }).fail(function(jqXHR, textStatus, errorThrown) {
        });
    }

    function load_chart(labels, Attendances, Schedules) {
        var ctx = document.getElementById('attendancesChart').getContext('2d');

        var attendancesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Attendances',
                        fill: false,
                        data: Attendances,
                        backgroundColor: 'rgb(255, 159, 64)',
                        borderColor: 'rgb(255, 159, 64, 0.8)',
                        borderWidth: 2,
                        radius: 0,
                    },
                    {
                        label: 'Schedules',
                        fill: false,
                        data: Schedules,
                        backgroundColor: 'rgb(54, 162, 235)',
                        borderColor: 'rgb(54, 162, 235, 0.8)',
                        borderWidth: 2,
                        radius: 0,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                layout: {
                    padding: {
                        top: 35,
                        right: 15
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            parser: 'YYYY-MM-DD',
                            unit: 'day',
                            displayFormats: {
                                day: 'D MMM'
                            },
                            tooltipFormat: 'D MMM YYYY'
                        },
                        ticks: {
                            source: 'data',
                            autoSkip: false,
                            stepSize: 1,
                            maxRotation: 60,
                            minRotation: 60
                        },
                        title: {
                            display: true,
                            text: 'Date'
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
