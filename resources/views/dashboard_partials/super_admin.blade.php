<div class="row g-4" id="app">

    {{-- Quotes --}}
    @include('dashboard_partials.quotes')

    {{-- Main Dashboard Column --}}
    <div class="col-lg-8">
        <div class="d-flex flex-column gap-4">

            {{-- Dashboard Summary --}}
            <div class="row g-3" id="dashboardSummary" v-cloak>

                {{-- Date Filter -- Always visible, no skeleton --}}
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-3 p-sm-4">
                            <form @submit.prevent="filterDashboard"
                                  class="row g-3 align-items-end">

                                <div class="col-12 col-sm-auto">
                                    <label for="filter" class="form-label fw-semibold text-secondary small text-uppercase tracking-wide">
                                        Reporting Period
                                    </label>

                                    <select
                                        id="filter"
                                        v-model="filter"
                                        name="filter"
                                        class="form-select form-select-sm rounded-2 border-0 bg-light"
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
                                        <option value="custom">Custom Range</option>
                                    </select>
                                </div>

                                <template v-if="filter === 'custom'">

                                    <div class="col-12 col-sm-auto">
                                        <label for="startDate" class="form-label fw-semibold text-secondary small text-uppercase tracking-wide">
                                            Start Date
                                        </label>
                                        <input
                                            type="text"
                                            id="startDate"
                                            class="form-control form-control-sm rounded-2 border-0 bg-light"
                                            v-model="startDate"
                                            placeholder="YYYY-MM-DD"
                                            autocomplete="off"
                                        >
                                    </div>

                                    <div class="col-12 col-sm-auto">
                                        <label for="endDate" class="form-label fw-semibold text-secondary small text-uppercase tracking-wide">
                                            End Date
                                        </label>
                                        <input
                                            type="text"
                                            id="endDate"
                                            class="form-control form-control-sm rounded-2 border-0 bg-light"
                                            v-model="endDate"
                                            placeholder="YYYY-MM-DD"
                                            autocomplete="off"
                                        >
                                    </div>

                                    <div class="col-12 col-sm-auto">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary rounded-2 px-3"
                                            @click="onCustomDateChange"
                                            :disabled="!startDate || !endDate"
                                        >
                                            <i class="fa fa-filter me-1"></i>
                                            Apply
                                        </button>
                                    </div>

                                </template>

                            </form>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards — Skeleton Loader -->
                <template v-if="isLoading">
                    <div class="col-12 col-sm-6 col-xl-4" v-for="n in 6" :key="'skel-'+n">
                        <div class="card border-0 shadow-sm rounded-3 h-100 stat-card">
                            <div class="card-body p-3 d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="skeleton skeleton-circle" style="width: 48px; height: 48px;"></div>
                                </div>
                                <div class="flex-grow-1 text-end overflow-hidden">
                                    <div class="skeleton skeleton-text ms-auto" style="width: 80px; height: 20px;"></div>
                                    <div class="skeleton skeleton-text ms-auto mt-2" style="width: 60px; height: 12px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Loaded Summary Cards -->
                <template v-else>
                    <div
                        class="col-12 col-sm-6 col-xl-4"
                        v-for="(card, index) in summaryCards"
                        :key="index"
                    >
                        <div class="card border-0 shadow-sm rounded-3 h-100 stat-card">
                            <div class="card-body p-3 d-flex align-items-center">

                                <div class="flex-shrink-0 me-3">
                                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                                         style="width: 48px; height: 48px;">
                                        <i :class="['fa', card.icon, 'text-white']"
                                           style="font-size: 1.2rem;"></i>
                                    </div>
                                </div>

                                <div class="flex-grow-1 text-end overflow-hidden">
                                    <div class="fw-bold fs-5 text-dark text-truncate lh-1 mb-1">
                                        <template v-if="card.currency">
                                            K@{{ formatCurrency(card.value) }}
                                        </template>
                                        <template v-else>
                                            @{{ formatNumber(card.value) }}
                                        </template>
                                    </div>
                                    <div class="fs-xs text-muted text-truncate text-uppercase tracking-wide">
                                        @{{ card.label }}
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </template>

            </div>

            {{-- Attendance Statistics --}}
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="card-title mb-0 fw-bold text-primary">
                        <i class="fa fa-chart-line me-2"></i>
                        Attendance Statistics
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div style="position: relative; min-height: 280px;">
                        <canvas id="attendancesChart" v-cloak></canvas>
                    </div>
                </div>
            </div>

            {{-- Students --}}
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0 fw-bold text-primary">
                        <i class="fa fa-users me-2"></i>
                        Recent Students
                    </h6>
                    <a href="{{ url('/students') }}" class="btn btn-sm btn-outline-primary rounded-2 px-3">
                        More Students
                        <i class="fa fa-arrow-right ms-1"></i>
                    </a>
                </div>

                <div class="card-body p-0 bg-white" style="max-height: 30em; overflow-y: auto;">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 fs-sm">
                            <thead class="bg-light sticky-top">
                                <tr class="text-uppercase fs-xs text-muted tracking-wide">
                                    <th class="text-center py-3" style="width: 70px;">Photo</th>
                                    <th class="py-3" style="min-width: 150px;">Name</th>
                                    <th class="py-3">Gender</th>
                                    <th class="d-none d-md-table-cell text-center py-3" style="min-width: 250px;">Course</th>
                                    <th class="text-center py-3" style="width: 80px;">View</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $student)
                                    @php
                                        $gender = $student->gender ?? null;
                                        $genderStyles = match ($gender) {
                                            'Male'   => ['bg-primary',   'text-white'],
                                            'Female' => ['bg-pink',      'text-white'],
                                            default  => ['bg-secondary', 'text-white'],
                                        };
                                    @endphp
                                    <tr>
                                        <td class="text-center py-3">
                                            <img
                                                class="rounded-circle border border-2 border-white shadow-sm"
                                                src="{{ asset('media/avatars/avatar2.jpg') }}"
                                                alt="Student"
                                                width="36"
                                                height="36"
                                                loading="lazy"
                                                style="object-fit: cover;"
                                            >
                                        </td>
                                        <td class="py-3">
                                            <div class="fw-semibold text-dark text-uppercase">
                                                {{ $student->fname }} {{ $student->sname }}
                                            </div>
                                            <div class="text-muted small">
                                                {{ $student->user?->email ?? 'No email registered' }}
                                            </div>
                                        </td>
                                        <td class="py-3">
                                            <span class="badge rounded-pill {{ $genderStyles[0] }} {{ $genderStyles[1] }} fw-semibold px-3 py-1">
                                                {{ $gender ?? 'Not specified' }}
                                            </span>
                                        </td>
                                        <td class="d-none d-md-table-cell text-center py-3">
                                            @if($student->course)
                                                <div class="">
                                                    {{ $student->course->name }}
                                                </div>
                                                <div class="text-muted small mt-1">
                                                    <i class="fa fa-clock me-1 opacity-50"></i>
                                                    {{ $student->course->duration }} days
                                                </div>
                                            @else
                                                <span class="badge rounded-pill bg-danger bg-opacity-10 fw-semibold px-3 py-1">
                                                    Not enrolled
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center py-3">
                                            <a
                                                href="{{ url('/viewstudent', $student->id) }}"
                                                class="btn btn-sm btn-outline-primary rounded-2"
                                                data-bs-toggle="tooltip"
                                                title="View Student"
                                                aria-label="View student"
                                            >
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <i class="fa fa-users fa-2x mb-3 d-block opacity-25"></i>
                                            No students found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Invoices --}}
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden" id="invoices" v-cloak>
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="card-title mb-0 fw-bold text-success">
                        <i class="fa fa-file-invoice me-2"></i>
                        Recent Invoices
                    </h6>
                </div>
                <div class="card-body p-0" style="max-height: 30em; overflow-y: auto;">

                    <!-- Skeleton Loader for Invoices -->
                    <div v-if="loading" class="table-responsive">
                        <table class="table align-middle mb-0 fs-sm">
                            <thead class="bg-light">
                                <tr class="text-uppercase fs-xs text-muted tracking-wide">
                                    <th class="py-3 ps-4" style="min-width: 200px;">Invoice No.</th>
                                    <th class="d-none d-sm-table-cell py-3" style="min-width:200px">Date</th>
                                    <th class="py-3" style="min-width: 150px;">Student</th>
                                    <th class="text-end py-3 pe-4">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="n in 6" :key="'inv-skel-'+n">
                                    <td class="py-3 ps-4">
                                        <div class="skeleton skeleton-text" style="width: 80px;"></div>
                                    </td>
                                    <td class="d-none d-sm-table-cell py-3">
                                        <div class="skeleton skeleton-text" style="width: 100px;"></div>
                                    </td>
                                    <td class="py-3">
                                        <div class="skeleton skeleton-text" style="width: 140px;"></div>
                                        <div class="skeleton skeleton-text mt-2" style="width: 90px;"></div>
                                    </td>
                                    <td class="text-end py-3 pe-4">
                                        <div class="skeleton skeleton-text ms-auto" style="width: 70px;"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Loaded Invoices -->
                    <div v-else class="table-responsive">
                        <table class="table align-middle mb-0 fs-sm">
                            <thead class="bg-light sticky-top">
                                <tr class="text-uppercase fs-xs text-muted tracking-wide">
                                    <th class="py-3 ps-4" style="min-width: 200px;">Invoice No.</th>
                                    <th class="d-none d-sm-table-cell py-3" style="min-width:200px">Date</th>
                                    <th class="py-3" style="min-width: 150px;">Student</th>
                                    <th class="text-end py-3 pe-4">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in info" :key="item.id">
                                    <td class="py-3 ps-4">
                                        <a
                                            :href="'{{ url('/view-invoice') }}/' + item.id"
                                            class="fw-semibold text-dark text-decoration-none"
                                        >
                                            @{{ item.invoice_number }}
                                        </a>
                                    </td>
                                    <td class="d-none d-sm-table-cell py-3">
                                        <span class="text-muted">
                                            @{{ timeCreated(item.date_created) }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <div class="fw-semibold text-dark">
                                            @{{ item.student?.fname || '' }}
                                            @{{ item.student?.mname || '' }}
                                            @{{ item.student?.sname || 'Unknown student' }}
                                        </div>
                                    </td>
                                    <td class="text-end py-3 pe-4">
                                        <span
                                            class="fw-bold"
                                            :class="Number(item.invoice_balance) > 0 ? 'text-danger' : 'text-success'"
                                        >
                                            K@{{ formatPrice(item.invoice_balance) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="info.length === 0">
                                    <td colspan="4" class="text-center text-muted py-5">
                                        <i class="fa fa-file-invoice fa-2x mb-3 d-block opacity-25"></i>
                                        No invoices found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- System Activities --}}
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0 fw-bold text-info">
                        <i class="fa fa-history me-2"></i>
                        System Activities
                    </h6>
                </div>

                <div class="card-body p-3" style="max-height: 500px; overflow-y: auto;">
                    <div class="table-responsive">
                        <table id="activitiesTable"
                            class="table align-middle mb-0 fs-sm">
                            <thead class="bg-light sticky-top">
                                <tr class="text-uppercase fs-xs text-muted tracking-wide">
                                    <th class="py-3 ps-4" style="width: 55%;">Activity</th>
                                    <th class="py-3" style="min-width: 100px;">Performed By</th>
                                    <th class="text-end py-3 pe-4" style="min-width: 200px;">Date &amp; Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activities as $activity)
                                    <tr>
                                        <td class="py-3 ps-4">
                                            {{ $activity->description }}
                                        </td>
                                        <td class="py-3">
                                            @php
                                                $causer = $activity->causer;
                                                $firstName = $causer?->administrator?->fname
                                                    ?? $causer?->instructor?->fname
                                                    ?? null;
                                                $surname = $causer?->administrator?->sname
                                                    ?? $causer?->instructor?->sname
                                                    ?? null;
                                            @endphp
                                            <span class="fw-medium">
                                                {{ trim(($firstName ?? '') . ' ' . ($surname ?? '')) ?: 'System' }}
                                            </span>
                                        </td>
                                        <td class="text-end py-3 pe-4 text-muted"
                                            data-order="{{ $activity->created_at?->timestamp ?? 0 }}"
                                            data-bs-toggle="tooltip"
                                            title="{{ $activity->created_at?->diffForHumans() }}">
                                            {{ $activity->created_at?->format('j M, Y - H:i:s') ?? '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-5">
                                            <i class="fa fa-history fa-2x mb-3 d-block opacity-25"></i>
                                            No activities found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
        <div class="d-flex flex-column gap-4">

            {{-- Instructor Performance --}}
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="card-title mb-0 fw-bold text-dark">
                        Instructor Performance
                        <span class="d-block fs-xs text-muted fw-normal text-uppercase tracking-wide mt-1">
                            {{ now()->format('F Y') }}
                        </span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    @php
                        $totalAttendances = $instructors->sum('attendance_count');
                    @endphp
                    <div class="table-responsive" style="max-height: 24em; overflow: auto;">
                        <table class="table align-middle mb-0 fs-sm">
                            <thead class="bg-light sticky-top">
                                <tr class="text-uppercase fs-xs text-muted tracking-wide">
                                    <th class="py-3 ps-4" style="min-width: 200px;">Instructor</th>
                                    <th class="text-center py-3">Attendance</th>
                                    <th class="text-end py-3 pe-4">Bonus</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($instructors as $instructor)
                                    @php
                                        $count = (int) ($instructor->attendance_count ?? 0);
                                        $percentage = $totalAttendances > 0
                                            ? ($count / $totalAttendances) * 100
                                            : 0;
                                        $estimatedPay = $instructor->estimated_pay
                                            ?? ($count * $settings->bonus);
                                    @endphp
                                    <tr>
                                        <td class="py-3 ps-4">
                                            <div class="fw-semibold text-dark">
                                                {{ $instructor->fname }}
                                                {{ $instructor->sname }}
                                            </div>
                                        </td>
                                        <td class="text-center py-3">
                                            <span class="fw-semibold">
                                                {{ number_format($count) }} ({{ number_format($percentage, 1) }}%)
                                            </span>
                                        </td>
                                        <td class="text-end fw-semibold py-3 pe-4">
                                            K{{ number_format($estimatedPay, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-5">
                                            <i class="fa fa-chalkboard-teacher fa-2x mb-3 d-block opacity-25"></i>
                                            No active instructors found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <th class="py-3 ps-4 fw-bold">Total</th>
                                    <th class="text-center py-3 fw-bold">
                                        {{ number_format($totalAttendances) }}
                                    </th>
                                    <th class="text-end py-3 pe-4 fw-bold">
                                        K{{ number_format($instructors->sum('estimated_pay'), 2) }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-top py-3 px-4">
                    <p class="mb-2 fs-sm">
                        Bonus rate:
                        <strong class="text-dark">K{{ number_format($settings->bonus, 2) }}</strong>
                        per attendance.
                        <a href="{{ url('/settings/#bonus') }}" class="text-decoration-none fw-medium ms-1">
                            Edit <i class="fa fa-pencil-alt ms-1 fs-xs"></i>
                        </a>
                    </p>
                    <p class="text-warning fs-sm mb-3">
                        <i class="fa fa-clock me-1"></i>
                        Bonuses are scheduled for the 28th.
                    </p>

                    {{-- Early Bonus Payment with Skeleton --}}
                    <div id="bonuses" v-cloak>
                        <button
                            type="button"
                            class="btn btn-primary rounded-pill px-4 w-100"
                            :disabled="paymentLoading"
                            @click="payEarly"
                        >
                            <template v-if="paymentLoading">
                                <span class="skeleton skeleton-text d-inline-block" style="width: 100px; height: 14px; vertical-align: middle;"></span>
                            </template>
                            <template v-else>
                                <i class="fa fa-money-bill-wave me-1"></i>
                                Pay Early
                            </template>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>


{{-- ============================================================
     VUE APPLICATIONS
============================================================ --}}

<script>
document.addEventListener('DOMContentLoaded', function () {
    const {
        createApp,
        ref,
        computed,
        onMounted,
        watch,
        nextTick
    } = Vue;

    /*
    |--------------------------------------------------------------------------
    | Early Bonus Payment
    |--------------------------------------------------------------------------
    */

    createApp({
        setup() {
            const paymentLoading = ref(false);

            const payEarly = async () => {
                const result = await Swal.fire({
                    title: 'Process Early Payment?',
                    text: 'You are about to process instructor bonus payments.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Proceed',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#28a745'
                });

                if (!result.isConfirmed || paymentLoading.value) {
                    return;
                }

                paymentLoading.value = true;
                NProgress.start();

                try {
                    await axios.post('/api/bonuses/pay-early');

                    await Swal.fire({
                        title: 'Success',
                        text: 'Early payment request completed successfully.',
                        icon: 'success'
                    });

                    window.location.reload();

                } catch (error) {
                    const message = error.response?.data?.message
                        || 'The payment could not be processed. Please try again.';

                    await Swal.fire({
                        title: 'Payment Failed',
                        text: message,
                        icon: 'error'
                    });

                } finally {
                    paymentLoading.value = false;
                    NProgress.done();
                }
            };

            return {
                paymentLoading,
                payEarly
            };
        }
    }).mount('#bonuses');


    /*
    |--------------------------------------------------------------------------
    | Invoice List
    |--------------------------------------------------------------------------
    */

    createApp({
        setup() {
            const loading = ref(true);
            const info = ref([]);

            const timeCreated = (date) => {
                if (!date) return '—';

                const parsed = moment(date);

                return parsed.isValid()
                    ? parsed.format('DD MMMM, YYYY')
                    : '—';
            };

            const formatPrice = (value) => {
                const amount = Number(value);

                return (Number.isFinite(amount) ? amount : 0)
                    .toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
            };

            const getStatusColor = (status) => {
                const colors = {
                    paid: 'success',
                    pending: 'warning',
                    overdue: 'danger',
                    partial: 'info'
                };

                return colors[String(status || '').toLowerCase()]
                    || 'secondary';
            };

            const getPaymentProgress = (item) => {
                const total = Number(item.invoice_total) || 0;
                const balance = Number(item.invoice_balance) || 0;

                if (total <= 0) return 0;

                const paid = total - balance;

                return Math.max(
                    0,
                    Math.min(100, Math.round((paid / total) * 100))
                );
            };

            const getProgressBarClass = (item) => {
                const progress = getPaymentProgress(item);

                if (progress >= 100) return 'bg-success';
                if (progress > 50) return 'bg-info';
                if (progress > 0) return 'bg-warning';

                return 'bg-danger';
            };

            const loadInvoices = async () => {
                loading.value = true;

                try {
                    const response = await axios.get('/api/invoices');

                    info.value = Array.isArray(response.data)
                        ? response.data
                        : [];

                } catch (error) {
                    console.error(
                        'Failed to load invoices:',
                        error.response?.data?.message || error.message
                    );

                    info.value = [];

                } finally {
                    loading.value = false;
                }
            };

            onMounted(loadInvoices);

            return {
                loading,
                info,
                timeCreated,
                formatPrice,
                getStatusColor,
                getPaymentProgress,
                getProgressBarClass,
                loadInvoices
            };
        }
    }).mount('#invoices');


    /*
    |--------------------------------------------------------------------------
    | Dashboard Summary
    |--------------------------------------------------------------------------
    */

    createApp({
        setup() {
            const filter = ref(@json($filter ?? 'today'));
            const startDate = ref('');
            const endDate = ref('');
            const isLoading = ref(true);

            const summaryInfo = ref({
                earningsTotal: 0,
                invoiceBalances: 0,
                studentCount: 0,
                expensesTotal: 0,
                expensesPayments: 0,
                attendanceCount: 0
            });

            let startPicker = null;
            let endPicker = null;

            const summaryCards = computed(() => [
                {
                    icon: 'fa-arrow-up',
                    value: summaryInfo.value.earningsTotal,
                    label: 'Sales',
                    currency: true
                },
                {
                    icon: 'fa-wallet',
                    value: summaryInfo.value.invoiceBalances,
                    label: 'Balances',
                    currency: true
                },
                {
                    icon: 'fa-users',
                    value: summaryInfo.value.studentCount,
                    label: 'Students',
                    currency: false
                },
                {
                    icon: 'fa-money-bill-wave',
                    value: summaryInfo.value.expensesPayments,
                    label: 'Expenses Paid',
                    currency: true
                },
                {
                    icon: 'fa-file-invoice-dollar',
                    value: summaryInfo.value.expensesTotal,
                    label: 'Expenses Approved',
                    currency: true
                },
                {
                    icon: 'fa-calendar-check',
                    value: summaryInfo.value.attendanceCount,
                    label: 'Attendances',
                    currency: false
                }
            ]);

            const formatNumber = (value) => {
                const number = Number(value);

                return (Number.isFinite(number) ? number : 0)
                    .toLocaleString('en-US');
            };

            const formatCurrency = (
                value,
                compact = true,
                decimals = 2,
                symbol = ''
            ) => {
                const amount = Number(value);

                if (!Number.isFinite(amount)) {
                    return '0.00';
                }

                const abs = Math.abs(amount);
                const sign = amount < 0 ? '-' : '';

                if (compact) {
                    if (abs >= 1_000_000_000) {
                        return sign + symbol + (abs / 1_000_000_000).toFixed(1) + 'B';
                    }

                    if (abs >= 1_000_000) {
                        return sign + symbol + (abs / 1_000_000).toFixed(1) + 'M';
                    }

                    if (abs >= 1_000) {
                        return sign + symbol + (abs / 1_000).toFixed(1) + 'K';
                    }
                }

                return sign + symbol + abs.toLocaleString('en-US', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                });
            };

            const showAlert = (
                message = '',
                detail = '',
                { icon = 'info' } = {}
            ) => {
                const options = {
                    icon,
                    toast: true,
                    timer: 3500,
                    timerProgressBar: true,
                    position: 'top-end',
                    showConfirmButton: false
                };

                if (message) options.title = message;
                if (detail) options.text = detail;

                return Swal.fire(options);
            };

            const filterDashboard = async () => {
                if (
                    filter.value === 'custom'
                    && (!startDate.value || !endDate.value)
                ) {
                    return;
                }

                if (
                    filter.value === 'custom'
                    && startDate.value > endDate.value
                ) {
                    showAlert(
                        'Invalid Date Range',
                        'The start date must not be later than the end date.',
                        { icon: 'error' }
                    );

                    return;
                }

                NProgress.start();
                isLoading.value = true;

                try {
                    const params = {
                        filter: filter.value
                    };

                    if (filter.value === 'custom') {
                        params.start_date = startDate.value;
                        params.end_date = endDate.value;
                    }

                    const response = await axios.get(
                        '/api/dashboardSummary',
                        { params }
                    );

                    summaryInfo.value = {
                        ...summaryInfo.value,
                        ...response.data
                    };

                } catch (error) {
                    const data = error.response?.data;

                    if (data?.errors) {
                        Object.values(data.errors)
                            .flat()
                            .forEach(message => {
                                showAlert('', message, { icon: 'error' });
                            });
                    } else {
                        showAlert(
                            'Dashboard Error',
                            data?.message || 'Unable to load dashboard summary.',
                            { icon: 'error' }
                        );
                    }

                } finally {
                    NProgress.done();
                    isLoading.value = false;
                }
            };

            const onFilterChange = () => {
                if (filter.value !== 'custom') {
                    filterDashboard();
                    return;
                }

                nextTick(() => {
                    const options = {
                        format: 'yyyy-mm-dd',
                        autoclose: true,
                        todayHighlight: true
                    };

                    if (startPicker) {
                        startPicker.destroy();
                    }

                    if (endPicker) {
                        endPicker.destroy();
                    }

                    startPicker = $('#startDate')
                        .datepicker(options)
                        .on('changeDate', function (event) {
                            startDate.value = event.format('yyyy-mm-dd');
                        })
                        .data('datepicker');

                    endPicker = $('#endDate')
                        .datepicker(options)
                        .on('changeDate', function (event) {
                            endDate.value = event.format('yyyy-mm-dd');
                        })
                        .data('datepicker');

                    const today = moment().format('YYYY-MM-DD');

                    if (!startDate.value) {
                        startDate.value = today;
                    }

                    if (!endDate.value) {
                        endDate.value = today;
                    }
                });
            };

            const onCustomDateChange = () => {
                filterDashboard();
            };

            onMounted(() => {
                filterDashboard();
            });

            return {
                filter,
                startDate,
                endDate,
                isLoading,
                summaryInfo,
                summaryCards,
                formatNumber,
                formatCurrency,
                filterDashboard,
                onFilterChange,
                onCustomDateChange,
                showAlert
            };
        }
    }).mount('#dashboardSummary');


    /*
    |--------------------------------------------------------------------------
    | System Activities DataTable
    |--------------------------------------------------------------------------
    */

    if ($.fn.DataTable && $('#activitiesTable').length) {
        $('#activitiesTable').DataTable({
            order: [[2, 'desc']],

            columnDefs: [
                {
                    targets: 2,
                    type: 'num',
                    render: function (data, type, row, meta) {
                        if (type === 'sort' || type === 'type') {
                            const cell = meta.settings.aoData[meta.row]
                                .anCells[meta.col];

                            return Number(
                                $(cell).attr('data-order') || 0
                            );
                        }

                        return data;
                    }
                },
                {
                    targets: 0,
                    orderable: false
                }
            ],

            pageLength: 10,
            autoWidth: false
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Bootstrap Tooltips
    |--------------------------------------------------------------------------
    */

    if (window.bootstrap?.Tooltip) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
            .forEach(element => {
                bootstrap.Tooltip.getOrCreateInstance(element);
            });
    }
});
</script>


{{-- ============================================================
     SKELETON LOADER STYLES
============================================================ --}}

<style>
    .skeleton {
        background: #e2e8f0;
        border-radius: 0.375rem;
        position: relative;
        overflow: hidden;
    }
    .skeleton::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        animation: shimmer 1.5s infinite;
    }
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    .skeleton-circle {
        border-radius: 50%;
    }
    .skeleton-text {
        height: 0.75rem;
        margin-bottom: 0.25rem;
    }
    .skeleton-input {
        height: 2rem;
        border-radius: 0.5rem;
    }
</style>


{{-- ============================================================
     ATTENDANCE CHART
============================================================ --}}

<script>
$(function () {
    let attendanceChart = null;
    let coursesChart = null;

    function loadAttendanceData() {
        $.getJSON("{{ url('/summaryData') }}")
            .done(function (data) {
                if (
                    !data
                    || !Array.isArray(data.attendances)
                    || !Array.isArray(data.schedules)
                ) {
                    return;
                }

                const attendanceMap = {};
                const scheduleMap = {};

                data.attendances.forEach(item => {
                    if (item.date) {
                        attendanceMap[item.date] = Number(item.count) || 0;
                    }
                });

                data.schedules.forEach(item => {
                    if (item.date) {
                        scheduleMap[item.date] = Number(item.count) || 0;
                    }
                });

                const labels = [
                    ...new Set([
                        ...Object.keys(attendanceMap),
                        ...Object.keys(scheduleMap)
                    ])
                ].sort();

                const attendanceValues = labels.map(
                    date => attendanceMap[date] || 0
                );

                const scheduleValues = labels.map(
                    date => scheduleMap[date] || 0
                );

                renderAttendanceChart(
                    labels,
                    attendanceValues,
                    scheduleValues
                );
            })
            .fail(function (xhr, status, error) {
                console.error(
                    'Unable to load attendance statistics:',
                    error
                );
            });
    }

    function renderAttendanceChart(labels, attendanceValues, scheduleValues) {
        const canvas = document.getElementById('attendancesChart');

        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        if (attendanceChart) {
            attendanceChart.destroy();
        }

        const hasTimeAdapter = typeof moment !== 'undefined';

        attendanceChart = new Chart(canvas.getContext('2d'), {
            type: 'line',

            data: {
                labels: labels,

                datasets: [
                    {
                        label: 'Attendances',
                        data: attendanceValues,
                        borderColor: 'rgba(255, 159, 64, 0.9)',
                        backgroundColor: 'rgba(255, 159, 64, 0.15)',
                        borderWidth: 2,
                        pointRadius: 2,
                        tension: 0.25
                    },
                    {
                        label: 'Schedules',
                        data: scheduleValues,
                        borderColor: 'rgba(54, 162, 235, 0.9)',
                        backgroundColor: 'rgba(54, 162, 235, 0.15)',
                        borderWidth: 2,
                        pointRadius: 2,
                        tension: 0.25
                    }
                ]
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
                    }
                },

                scales: {
                    x: {
                        ticks: {
                            autoSkip: true,
                            maxRotation: 45,
                            minRotation: 0
                        },
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },

                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        title: {
                            display: true,
                            text: 'Count'
                        }
                    }
                }
            }
        });
    }

    loadAttendanceData();
});
</script>
