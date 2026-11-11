<div class="row g-4 items-push" id="dashboardApp">

    <!-- Quotes Partial -->
    <div class="col-12">
        @include('dashboard_partials.quotes')
    </div>

    <!-- Students Block -->
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden h-100">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-bold text-primary">
                    <i class="fa fa-users me-2"></i>
                    Students
                </h6>
                <div class="d-flex gap-1">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary rounded-2"
                            data-bs-toggle="tooltip"
                            title="Refresh"
                            aria-label="Refresh students">
                        <i class="fa fa-sync-alt"></i>
                    </button>
                    <a href="{{ url('/students') }}"
                       class="btn btn-sm btn-outline-primary rounded-2"
                       data-bs-toggle="tooltip"
                       title="View all students"
                       aria-label="View all students">
                        <i class="fa fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="card-body p-0" style="max-height: 30em; overflow-y: auto;">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 fs-sm">
                        <thead class="bg-light sticky-top">
                            <tr class="text-uppercase fs-xs text-muted tracking-wide">
                                <th class="text-center py-3" style="min-width: 70px;">Photo</th>
                                <th class="py-3" style="min-width: 150px;">Name</th>
                                <th class="d-none d-sm-table-cell text-center py-3" style="min-width: 180px;">Course</th>
                                <th class="text-center py-3" style="width: 70px;">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $student)
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
                                            {{ $student->fname }} {{ $student->mname }} {{ $student->sname }}
                                        </div>
                                        <div class="text-muted small">
                                            {{ $student->user->email ?? 'No email registered' }}
                                        </div>
                                    </td>
                                    <td class="d-none d-sm-table-cell text-center py-3">
                                        @if (isset($student->course->name))
                                            <span class="badge rounded-pill bg-primary bg-opacity-10 text-white fw-semibold px-3 py-1">
                                                {{ $student->course->name }}
                                            </span>
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
                                            href="{{ route('students.show', $student->id) }}"
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
                                    <td colspan="4" class="text-center text-muted py-5">
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
    </div>

    <!-- Invoices Block -->
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden h-100" id="invoicesBlock">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0 fw-bold text-success">
                    <i class="fa fa-file-invoice me-2"></i>
                    Invoices
                </h6>
                <div class="d-flex gap-1">
                    <button type="button"
                            class="btn btn-sm btn-outline-secondary rounded-2"
                            data-bs-toggle="tooltip"
                            title="Refresh"
                            aria-label="Refresh invoices"
                            @click="loadInvoices">
                        <i class="fa fa-sync-alt"></i>
                    </button>
                    <a href="{{ url('/invoices') }}"
                       class="btn btn-sm btn-outline-primary rounded-2"
                       data-bs-toggle="tooltip"
                       title="View all invoices"
                       aria-label="View all invoices">
                        <i class="fa fa-arrow-right"></i>
                    </a>
                </div>
            </div>

            <div class="card-body p-0" style="max-height: 30em; overflow-y: auto;">

                <!-- Skeleton Loader -->
                <div v-if="loading" class="table-responsive">
                    <table class="table align-middle mb-0 fs-sm">
                        <thead class="bg-light">
                            <tr class="text-uppercase fs-xs text-muted tracking-wide">
                                <th class="py-3 ps-4" style="min-width: 200px;">Invoice No.</th>
                                <th class="d-none d-sm-table-cell py-3" style="min-width:200px">Date</th>
                                <th class="py-3" style="min-width: 150px;">Student</th>
                                <th class="text-end py-3 pe-4">Balance</th>
                                <th class="text-center py-3" style="width: 70px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="n in 5" :key="'inv-skel-'+n">
                                <td class="py-3 ps-4">
                                    <div class="skeleton skeleton-text" style="width: 80px;"></div>
                                </td>
                                <td class="d-none d-sm-table-cell py-3">
                                    <div class="skeleton skeleton-text" style="width: 100px;"></div>
                                </td>
                                <td class="py-3">
                                    <div class="skeleton skeleton-text" style="width: 140px;"></div>
                                </td>
                                <td class="text-end py-3 pe-4">
                                    <div class="skeleton skeleton-text ms-auto" style="width: 70px;"></div>
                                </td>
                                <td class="text-center py-3">
                                    <div class="skeleton skeleton-circle mx-auto" style="width: 24px; height: 24px;"></div>
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
                                <th class="text-center py-3" style="width: 70px;">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in info" :key="item.id">
                                <td class="py-3 ps-4">
                                    <a
                                        :href="'{{ route('invoices.show', '__invoice__') }}'.replace('__invoice__', encodeURIComponent(item.id))"
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
                                    <div class="fw-semibold text-dark text-uppercase">
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
                                <td class="text-center py-3">
                                    <a
                                        :href="'{{ route('invoices.show', '__invoice__') }}'.replace('__invoice__', encodeURIComponent(item.id))"
                                        class="btn btn-sm btn-outline-primary rounded-2"
                                        data-bs-toggle="tooltip"
                                        title="View Invoice"
                                        aria-label="View invoice"
                                    >
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr v-if="info.length === 0">
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fa fa-file-invoice fa-2x mb-3 d-block opacity-25"></i>
                                    No invoices found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>


{{-- ============================================================
     VUE APPLICATION — Invoices
============================================================ --}}

<script>
document.addEventListener('DOMContentLoaded', function () {
    const { createApp, ref, onMounted } = Vue;

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
                loadInvoices
            };
        }
    }).mount('#invoicesBlock');

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
</style>
