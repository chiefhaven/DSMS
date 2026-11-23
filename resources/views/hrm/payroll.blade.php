@extends('layouts.backend')

@section('content')
    @include('hrm.partials.hrmNav', ['hrmActive' => 'payroll'])

    <div class="content content-full" id="payroll">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <h2 class="fs-4 fw-semibold mb-0">Payroll</h2>
            @can('view payroll')
                @can('process payroll')
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" @click="openAddPayroll">
                        <i class="fa fa-plus me-1"></i> Add payroll
                    </button>
                @endcan
            @endcan
        </div>

        @can('view payroll')
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <label for="payroll-month-filter-mode" class="fs-sm text-muted mb-0">Filter</label>
            <select id="payroll-month-filter-mode" class="form-select form-select-sm w-auto" v-model="monthFilterMode" @change="onMonthFilterModeChange">
                <option value="month">Month</option>
                <option value="range">Custom range</option>
            </select>

            <template v-if="monthFilterMode === 'month'">
                <label for="payroll-month-filter" class="fs-sm text-muted mb-0">Month</label>
                <input type="text" id="payroll-month-filter" class="form-control form-control-sm w-auto js-monthpicker" v-model="monthFilter" placeholder="YYYY-MM" autocomplete="off">
            </template>
            <template v-else>
                <label for="payroll-month-from" class="fs-sm text-muted mb-0">From</label>
                <input type="text" id="payroll-month-from" class="form-control form-control-sm w-auto js-monthpicker" v-model="monthFrom" placeholder="YYYY-MM" autocomplete="off">
                <label for="payroll-month-to" class="fs-sm text-muted mb-0">To</label>
                <input type="text" id="payroll-month-to" class="form-control form-control-sm w-auto js-monthpicker" v-model="monthTo" placeholder="YYYY-MM" autocomplete="off">
            </template>

            <button type="button" class="btn btn-sm btn-outline-secondary" v-if="hasMonthFilter" @click="clearMonthFilter">Clear</button>
            <span v-if="groupFilter" class="badge bg-primary d-inline-flex align-items-center gap-2">
                Group: @{{ groupFilter.name }}
                <button type="button" class="btn-close btn-close-white" style="font-size: .6rem;" aria-label="Clear group filter" @click="clearGroupFilter"></button>
            </span>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="block block-rounded mb-0 h-100">
                    <div class="block-content block-content-full">
                        <div class="fs-sm fw-semibold text-uppercase text-muted">Gross earnings</div>
                        <div class="fs-4 fw-semibold">K@{{ formatAmount(payrollSummary.gross || 0) }}</div>
                        <div class="fs-sm text-muted">Basic + allowances + bonus</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="block block-rounded mb-0 h-100">
                    <div class="block-content block-content-full">
                        <div class="fs-sm fw-semibold text-uppercase text-muted">Total PAYE</div>
                        <div class="fs-4 fw-semibold">K@{{ formatAmount(payrollSummary.paye || 0) }}</div>
                        <div class="fs-sm text-muted">Employee tax deducted</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="block block-rounded mb-0 h-100">
                    <div class="block-content block-content-full">
                        <div class="fs-sm fw-semibold text-uppercase text-muted">Total pension</div>
                        <div class="fs-4 fw-semibold">K@{{ formatAmount(payrollSummary.employee_pension || 0) }}</div>
                        <div class="fs-sm text-muted">Employer contribution K@{{ formatAmount(payrollSummary.employer_pension || 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="block block-rounded mb-0 h-100">
                    <div class="block-content block-content-full">
                        <div class="fs-sm fw-semibold text-uppercase text-muted">Total net</div>
                        <div class="fs-4 fw-semibold">K@{{ formatAmount(payrollSummary.net_pay || 0) }}</div>
                        <div class="fs-sm text-muted">Net pay for filtered payrolls</div>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        <div class="block block-rounded">
            <ul class="nav nav-tabs nav-tabs-block" role="tablist" aria-label="Payroll views">
                @can('view payroll')
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="payrolls-tab" data-bs-toggle="tab" data-bs-target="#payrolls-panel"
                                type="button" role="tab" aria-controls="payrolls-panel" aria-selected="true">
                            <i class="fa fa-coins me-2"></i>All Payrolls
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="groups-tab" data-bs-toggle="tab" data-bs-target="#groups-panel"
                                type="button" role="tab" aria-controls="groups-panel" aria-selected="false">
                            <i class="fa fa-layer-group me-2"></i>All payroll groups
                        </button>
                    </li>
                @endcan
            </ul>
            <div class="block-content tab-content">
                @can('view payroll')
                <div class="tab-pane fade show active" id="payrolls-panel" role="tabpanel" aria-labelledby="payrolls-tab">
                    <div class="table-responsive">
                        <table id="payrollTable" class="table table-bordered table-striped table-vcenter w-100 fs-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th class="text-center" style="min-width: 100px;">Actions</th>
                                    <th style="min-width: 13rem;">Employee</th>
                                    <th>Type</th>
                                    <th style="min-width: 9rem;">Month</th>
                                    <th>Basic salary</th>
                                    <th>Allowances</th>
                                    <th style="min-width: 9rem;">Bonus</th>
                                    <th>Deductions</th>
                                    <th>Net pay</th>
                                    <th style="min-width: 8rem;">Payment date</th>
                                    <th>Status</th>
                                    <th>Processed by</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="groups-panel" role="tabpanel" aria-labelledby="groups-tab">
                    <div class="table-responsive">
                        <table id="groupsTable" class="table table-bordered table-striped table-vcenter w-100 fs-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th style="min-width: 13rem;">Group</th>
                                    <th style="min-width: 9rem;">Month</th>
                                    <th class="text-end">Employees</th>
                                    <th class="text-end" style="min-width: 9rem;">Total net pay</th>
                                    <th style="min-width: 9rem;">Payment status</th>
                                    <th>Created by</th>
                                    <th style="min-width: 9rem;">Created on</th>
                                    <th class="text-center" style="min-width: 120px;">Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                @endcan
            </div>
        </div>

        @can('view payroll')
        @can('process payroll')
        {{-- Add payroll (group / multi-employee) modal --}}
        <div class="modal fade" id="payroll-add-modal" tabindex="-1" aria-labelledby="payroll-add-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title text-white" id="payroll-add-modal-title">@{{ groupModalMode === 'review' ? 'Review payroll group' : (groupForm.id ? 'Edit payroll group' : 'Add payroll') }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form @submit.prevent="saveGroup">
                        <div class="modal-body" style="max-height: calc(100vh - 230px); overflow-y: auto;">
                            <div v-if="groupForm.error" class="alert alert-danger" role="alert">@{{ groupForm.error }}</div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="group-month">Pay month</label>
                                    <input id="group-month" type="text" class="form-control js-monthpicker" v-model="groupForm.payment_month" placeholder="YYYY-MM" autocomplete="off" required @change="loadBonusPreviews">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="group-name">Group name <span class="text-muted fs-sm">(optional)</span></label>
                                    <input id="group-name" type="text" class="form-control" v-model="groupForm.name" maxlength="255" :placeholder="defaultGroupName">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <div class="w-100">
                                        <label class="form-label d-block">Status</label>
                                        <span class="badge bg-warning text-dark">Pending review</span>
                                        <div v-if="groupModalMode === 'review'" class="fs-xs text-muted mt-1">Review and edit this group as needed, then approve and finalize to save and submit it for payment.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <span class="ms-auto fs-sm text-muted">@{{ selectedCount }} employee@{{ selectedCount === 1 ? '' : 's' }} selected</span>
                            </div>

                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-vcenter mb-0 fs-sm">
                                    <thead class="bg-light" style="position: sticky; top: 0; z-index: 1;">
                                        <tr>
                                            <th style="width: 2.5rem;" class="text-center">
                                                <input type="checkbox" class="form-check-input" :checked="allSelected" @change="toggleSelectAll" aria-label="Select all employees">
                                            </th>
                                            <th style="min-width: 13rem;">Employee</th>
                                            <th>Type</th>
                                            <th style="min-width: 8rem;">Basic salary (K)</th>
                                            <th style="min-width: 7rem;">Allowances (K)</th>
                                            <th style="min-width: 7rem;">Deductions (K)</th>
                                            <th class="text-end">Bonus</th>
                                            <th class="text-end">PAYE</th>
                                            <th class="text-end">Pension</th>
                                            <th class="text-end">Net pay</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="!employeeRows.length">
                                            <td colspan="10" class="text-center text-muted py-3">
                                                <span v-if="employeesLoading"><span class="spinner-border spinner-border-sm me-2"></span>Loading employees...</span>
                                                <span v-else>No employees available.</span>
                                            </td>
                                        </tr>
                                        <tr v-for="row in employeeRows" :key="row.key" :class="{ 'table-active': row.selected }">
                                            <td class="text-center">
                                                <input type="checkbox" class="form-check-input" v-model="row.selected" :aria-label="'Select ' + row.name">
                                            </td>
                                            <td class="fw-semibold">@{{ row.name }}</td>
                                            <td>@{{ row.type }}</td>
                                            <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" v-model.number="row.basic_salary" :disabled="!row.selected" :required="row.selected"></td>
                                            <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" v-model.number="row.allowances" :disabled="!row.selected"></td>
                                            <td><input type="number" step="0.01" min="0" class="form-control form-control-sm" v-model.number="row.deductions" :disabled="!row.selected"></td>
                                            <td class="text-end text-nowrap">
                                                <span v-if="row.type === 'Instructor'">K@{{ formatAmount(rowBonus(row)) }} <small class="text-muted d-block">@{{ rowAttendances(row) }} att.</small></span>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td class="text-end text-nowrap">
                                                <span v-if="rowPaye(row) > 0">K@{{ formatAmount(rowPaye(row)) }}</span>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td class="text-end text-nowrap">
                                                <span v-if="rowEmployeePension(row) > 0">K@{{ formatAmount(rowEmployeePension(row)) }}</span>
                                                <span v-else class="text-muted">-</span>
                                            </td>
                                            <td class="text-end fw-semibold text-nowrap">K@{{ formatAmount(rowNet(row)) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="bg-body-light rounded-3 p-3 mt-3 d-flex flex-wrap gap-4">
                                <div>
                                    <span class="text-muted fs-sm d-block">Instructor bonus rate</span>
                                    <span class="fw-semibold">K@{{ formatAmount(bonuses.rate) }} per attendance</span>
                                </div>
                                <div>
                                    <span class="text-muted fs-sm d-block">Total PAYE (@{{ selectedCount }} selected)</span>
                                    <span class="fw-semibold">K@{{ formatAmount(totalPaye) }}</span>
                                </div>
                                <div>
                                    <span class="text-muted fs-sm d-block">Total pension (@{{ selectedCount }} selected)</span>
                                    <span class="fw-semibold">K@{{ formatAmount(totalEmployeePension) }}</span>
                                </div>
                                <div>
                                    <span class="text-muted fs-sm d-block">Total net pay (@{{ selectedCount }} selected)</span>
                                    <span class="fw-semibold fs-5">K@{{ formatAmount(totalNet) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@{{ groupModalMode === 'review' ? 'Close' : 'Cancel' }}</button>
                            <button v-if="groupModalMode !== 'review'" type="submit" class="btn btn-primary" :disabled="groupForm.saving || !selectedCount">
                                <span v-if="groupForm.saving" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                @{{ groupForm.id ? 'Save changes' : 'Create payroll (Pending review)' }} (@{{ selectedCount }})
                            </button>
                            <button v-if="groupModalMode === 'review'" type="button" class="btn btn-success" @click="finalizeGroupFromForm" :disabled="groupForm.saving || groupReviewFinalizing || !groupForm.id || !selectedCount">
                                <span v-if="groupReviewFinalizing" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                <i v-else class="fa fa-check-double me-1"></i> Approve &amp; finalize group
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @endcan
        @endcan

        {{-- Payroll group details modal --}}
        @can('view payroll')
        <div class="modal fade" id="payroll-group-modal" tabindex="-1" aria-labelledby="payroll-group-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title text-white" id="payroll-group-modal-title">@{{ groupDetails?.group.name || 'Payroll group' }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="max-height: calc(100vh - 230px); overflow-y: auto;">
                        <div v-if="groupDetailsError" class="alert alert-danger" role="alert">@{{ groupDetailsError }}</div>
                        <div v-if="groupDetailsLoading" class="text-center text-muted py-5" role="status">
                            <span class="spinner-border spinner-border-sm me-2"></span>Loading payroll group...
                        </div>
                        <template v-if="groupDetails && !groupDetailsLoading">
                            <div class="row g-2 mb-3">
                                <div class="col-sm-6 col-lg-3">
                                    <div class="bg-body-light rounded-3 p-3 h-100">
                                        <span class="text-muted fs-sm d-block">Pay month</span>
                                        <span class="fw-semibold">@{{ groupDetails.group.month }}</span>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="bg-body-light rounded-3 p-3 h-100">
                                        <span class="text-muted fs-sm d-block">Employees</span>
                                        <span class="fw-semibold">@{{ groupDetails.summary.employees }}</span>
                                        <small class="text-muted d-block">@{{ groupDetails.summary.paid }} paid / @{{ groupDetails.summary.final }} final / @{{ groupDetails.summary.pending }} pending</small>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="bg-body-light rounded-3 p-3 h-100">
                                        <span class="text-muted fs-sm d-block">Total PAYE</span>
                                        <span class="fw-semibold">K@{{ formatAmount(groupDetails.summary.paye || 0) }}</span>
                                        <span class="text-muted fs-sm d-block mt-2">Total pension (employee)</span>
                                        <span class="fw-semibold">K@{{ formatAmount(groupDetails.summary.employee_pension || 0) }}</span>
                                        <small class="text-muted d-block">Total net: K@{{ formatAmount(groupDetails.summary.net_pay) }}</small>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="bg-body-light rounded-3 p-3 h-100">
                                        <span class="text-muted fs-sm d-block">Created</span>
                                        <span class="fw-semibold">@{{ groupDetails.group.created_on }}</span>
                                        <small class="text-muted d-block">by @{{ groupDetails.group.created_by }}</small>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-striped table-vcenter mb-0 fs-sm">
                                    <thead class="bg-light">
                                        <tr>
                                            <th style="min-width: 11rem;">Employee</th>
                                            <th>Type</th>
                                            <th class="text-end">Basic salary</th>
                                            <th class="text-end">Allowances</th>
                                            <th class="text-end">Bonus</th>
                                            <th class="text-end">Deductions</th>
                                            <th class="text-end">PAYE</th>
                                            <th class="text-end">Pension</th>
                                            <th class="text-end">Net pay</th>
                                            <th>Payment date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(record, index) in groupDetails.records" :key="index">
                                            <td class="fw-semibold">@{{ record.employee }}</td>
                                            <td>@{{ record.type }}</td>
                                            <td class="text-end">K@{{ formatAmount(record.basic_salary) }}</td>
                                            <td class="text-end">K@{{ formatAmount(record.allowances) }}</td>
                                            <td class="text-end">
                                                K@{{ formatAmount(record.bonus) }}
                                                <small v-if="record.type === 'Instructor'" class="text-muted d-block">@{{ record.bonus_attendances }} att.</small>
                                            </td>
                                            <td class="text-end">K@{{ formatAmount(record.deductions) }}</td>
                                            <td class="text-end">K@{{ formatAmount(record.paye || 0) }}</td>
                                            <td class="text-end">K@{{ formatAmount(record.employee_pension || 0) }}</td>
                                            <td class="text-end fw-semibold">K@{{ formatAmount(record.net_pay) }}</td>
                                            <td>@{{ record.payment_date || '-' }}</td>
                                            <td>
                                                <span class="badge" :class="record.status === 'Paid' ? 'bg-success' : (record.status === 'Final' ? 'bg-primary' : 'bg-warning text-dark')">@{{ record.status }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-light fw-semibold">
                                        <tr>
                                            <td colspan="2">Totals</td>
                                            <td class="text-end">K@{{ formatAmount(groupDetails.summary.basic_salary) }}</td>
                                            <td class="text-end">K@{{ formatAmount(groupDetails.summary.allowances) }}</td>
                                            <td class="text-end">K@{{ formatAmount(groupDetails.summary.bonus) }}</td>
                                            <td class="text-end">K@{{ formatAmount(groupDetails.summary.deductions) }}</td>
                                            <td class="text-end">K@{{ formatAmount(groupDetails.summary.paye || 0) }}</td>
                                            <td class="text-end">K@{{ formatAmount(groupDetails.summary.employee_pension || 0) }}</td>
                                            <td class="text-end">K@{{ formatAmount(groupDetails.summary.net_pay) }}</td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </template>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        @can('process payroll')
                        <a class="btn btn-success"
                           :class="{ disabled: !groupDetails || (groupDetails.summary.pending || 0) > 0 }"
                           :href="groupDetails && (groupDetails.summary.pending || 0) === 0 ? ('{{ url('hrm/payroll/groups') }}/' + groupDetails.group.id + '/export-standard-bank-csv') : '#'"
                           @click="(!groupDetails || (groupDetails.summary.pending || 0) > 0) && $event.preventDefault()">
                            <i class="fa fa-file-csv me-1"></i> Export Standard Bank CSV
                        </a>
                        <a class="btn btn-outline-success"
                           :class="{ disabled: !groupDetails || (groupDetails.summary.pending || 0) > 0 }"
                           :href="groupDetails && (groupDetails.summary.pending || 0) === 0 ? ('{{ url('hrm/payroll/groups') }}/' + groupDetails.group.id + '/export-paye-csv') : '#'"
                           @click="(!groupDetails || (groupDetails.summary.pending || 0) > 0) && $event.preventDefault()">
                            <i class="fa fa-file-csv me-1"></i> Export PAYE CSV
                        </a>
                        @endcan
                        <button type="button" class="btn btn-primary" @click="filterByGroup" :disabled="!groupDetails">
                            <i class="fa fa-filter me-1"></i> Open in All Payrolls
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endcan
    </div>

    <script>
        createApp({
            setup() {
                @can('view payroll')
                const monthFilter = ref(@json($defaultPayrollMonth ?? ''));
                const monthFrom = ref('');
                const monthTo = ref('');
                const monthFilterMode = ref('month');
                const groupFilter = ref(null);
                const payrollSummary = ref({
                    count: 0, gross: 0, total_deductions: 0, paye: 0,
                    employee_pension: 0, employer_pension: 0, net_pay: 0,
                    paid_count: 0, pending_count: 0, final_count: 0
                });
                const payrollSummaryLoading = ref(false);
                const formatNumber = (value) => Number(value || 0).toLocaleString('en-US');
                const hasMonthFilter = computed(() => monthFilterMode.value === 'range'
                    ? !!(monthFrom.value || monthTo.value)
                    : !!monthFilter.value);
                const monthFilterParams = () => monthFilterMode.value === 'range'
                    ? { from_month: monthFrom.value || '', to_month: monthTo.value || '' }
                    : { month: monthFilter.value || '' };

                const employeeRows = ref([]);
                const employeesLoading = ref(false);
                const bonuses = ref({ rate: 0, attendances: {} });
                const groupForm = ref(emptyGroupForm());

                const form = ref(emptyForm());
                const bonus = ref({ attendances: 0, rate: 0, bonus: 0 });
                const bonusLoading = ref(false);
                let table = null;
                let groupsTable = null;
                @endcan

                const getModal = (id) => {
                    const modalElement = document.getElementById(id);
                    return modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;
                };

                const toast = (message) => Swal.fire({
                    icon: 'success', title: message, toast: true, timer: 3000,
                    position: 'top-end', showConfirmButton: false, timerProgressBar: true
                });

                @can('view payroll')
                function emptyGroupForm() {
                    return { id: null, payment_month: '', name: '', saving: false, error: '' };
                }

                function emptyForm() {
                    return {
                        id: null, employee: '', employeeName: '', employee_key: '', payment_month: '',
                        basic_salary: null, allowances: null, deductions: null, paye: 0, paye_applicable: false,
                        employee_pension: 0, employer_pension: 0, pension_applicable: false
                    };
                }
                @endcan

                @can('view payroll')
                const payeConfig = ref(@json($payeConfig ?? config('payroll.paye')));
                const pensionConfig = ref(@json($pensionConfig ?? \App\Models\Setting::pensionConfig()));
                const formatAmount = (value) => Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const formatMonth = (month) => {
                    if (!month) return '-';
                    const [year, monthNumber] = month.split('-');
                    return new Date(Number(year), Number(monthNumber) - 1, 1).toLocaleString('en-GB', { month: 'long', year: 'numeric' });
                };

                const syncSingleMonthPicker = (selector, valueRef) => {
                    const $picker = $(selector);
                    if (!$picker.length || !$.fn.datepicker) {
                        return;
                    }
                    const value = valueRef.value || '';
                    if (value) {
                        const [year, monthNumber] = value.split('-').map(Number);
                        if (year && monthNumber) {
                            $picker.datepicker('update', new Date(year, monthNumber - 1, 1));
                        } else {
                            $picker.val(value);
                        }
                    } else {
                        $picker.datepicker('update', '');
                        $picker.val('');
                    }
                };

                const initSingleMonthPicker = (selector, valueRef) => {
                    const $picker = $(selector);
                    if (!$picker.length || !$.fn.datepicker || $picker.data('payroll-monthpicker-init')) {
                        syncSingleMonthPicker(selector, valueRef);
                        return;
                    }
                    $picker.datepicker({
                        format: 'yyyy-mm',
                        minViewMode: 'months',
                        startView: 'months',
                        autoclose: true,
                        clearBtn: true
                    }).on('changeDate clearDate', (event) => {
                        const value = event.format ? event.format('yyyy-mm') : ($picker.val() || '');
                        if (valueRef.value !== value) {
                            valueRef.value = value;
                        }
                        reloadTable();
                    }).on('change', () => {
                        const value = $picker.val() || '';
                        if (valueRef.value !== value) {
                            valueRef.value = value;
                        }
                        reloadTable();
                    });
                    $picker.data('payroll-monthpicker-init', true);
                    syncSingleMonthPicker(selector, valueRef);
                };

                const initMonthFilterPickers = () => {
                    if (monthFilterMode.value === 'range') {
                        initSingleMonthPicker('#payroll-month-from', monthFrom);
                        initSingleMonthPicker('#payroll-month-to', monthTo);
                    } else {
                        initSingleMonthPicker('#payroll-month-filter', monthFilter);
                    }
                };

                const syncMonthFilterPickers = () => {
                    if (monthFilterMode.value === 'range') {
                        syncSingleMonthPicker('#payroll-month-from', monthFrom);
                        syncSingleMonthPicker('#payroll-month-to', monthTo);
                    } else {
                        syncSingleMonthPicker('#payroll-month-filter', monthFilter);
                    }
                };

                const onMonthFilterModeChange = () => {
                    if (monthFilterMode.value === 'range') {
                        monthFilter.value = '';
                    } else {
                        monthFrom.value = '';
                        monthTo.value = '';
                    }
                    setTimeout(() => {
                        initMonthFilterPickers();
                        syncMonthFilterPickers();
                        reloadTable();
                    }, 0);
                };

                let syncingGroupMonthPicker = false;
                const syncGroupMonthPicker = () => {
                    const $picker = $('#group-month');
                    if (!$picker.length || !$.fn.datepicker) {
                        return;
                    }
                    const value = groupForm.value.payment_month || '';
                    if (($picker.val() || '') === value) {
                        return;
                    }
                    syncingGroupMonthPicker = true;
                    try {
                        if (value) {
                            const [year, monthNumber] = value.split('-').map(Number);
                            if (year && monthNumber) {
                                $picker.datepicker('update', new Date(year, monthNumber - 1, 1));
                            } else {
                                $picker.val(value);
                            }
                        } else {
                            $picker.datepicker('update', '');
                            $picker.val('');
                        }
                    } finally {
                        syncingGroupMonthPicker = false;
                    }
                };

                const initGroupMonthPicker = () => {
                    const $picker = $('#group-month');
                    if (!$picker.length || !$.fn.datepicker || $picker.data('payroll-monthpicker')) {
                        return;
                    }
                    $picker.datepicker({
                        format: 'yyyy-mm',
                        minViewMode: 'months',
                        startView: 'months',
                        autoclose: true
                    }).on('changeDate clearDate', (event) => {
                        if (syncingGroupMonthPicker) return;
                        const value = event.format ? event.format('yyyy-mm') : ($picker.val() || '');
                        if (groupForm.value.payment_month !== value) {
                            groupForm.value.payment_month = value;
                        }
                        loadBonusPreviews();
                    }).on('change', () => {
                        if (syncingGroupMonthPicker) return;
                        const value = $picker.val() || '';
                        if (groupForm.value.payment_month !== value) {
                            groupForm.value.payment_month = value;
                        }
                        loadBonusPreviews();
                    });
                    $picker.data('payroll-monthpicker', true);
                    syncGroupMonthPicker();
                };

                // ----- Add payroll (group) -----
                const selectedRows = computed(() => employeeRows.value.filter((row) => row.selected));
                const selectedCount = computed(() => selectedRows.value.length);
                const allSelected = computed(() => employeeRows.value.length > 0 && selectedRows.value.length === employeeRows.value.length);
                const defaultGroupName = computed(() => groupForm.value.payment_month ? 'Payroll ' + formatMonth(groupForm.value.payment_month) : 'Payroll');

                const rowAttendances = (row) => Number(bonuses.value.attendances?.[row.id] || 0);
                const rowBonus = (row) => row.type === 'Instructor'
                    ? Math.round(rowAttendances(row) * (bonuses.value.rate || 0) * 100) / 100
                    : 0;
                const rowGross = (row) => {
                    const basic = parseFloat(row.basic_salary) || 0;
                    const bonus = rowBonus(row);
                    const grossSalary = row.type === 'Instructor' ? Math.max(basic, bonus) : basic + bonus;
                    return grossSalary + (parseFloat(row.allowances) || 0);
                };
                const computePaye = (taxable, applicable) => {
                    const config = payeConfig.value || {};
                    if (!applicable || !(config.enabled ?? true)) return 0;
                    const amount = Math.max(parseFloat(taxable) || 0, 0);
                    const bands = Array.isArray(config.bands) ? config.bands : [];
                    if (bands.length) {
                        let tax = 0;
                        let previous = 0;
                        bands.forEach((band) => {
                            const limit = band.up_to === null || band.up_to === undefined ? null : Number(band.up_to);
                            const rate = Number(band.rate || 0);
                            const upper = limit === null ? amount : Math.min(limit, amount);
                            if (upper > previous) {
                                tax += (upper - previous) * rate;
                                previous = upper;
                            }
                        });
                        if (amount > previous) {
                            const lastRate = Number(bands[bands.length - 1]?.rate || 0);
                            tax += (amount - previous) * lastRate;
                        }
                        return Math.round(tax * 100) / 100;
                    }
                    return Math.round(amount * Number(config.rate || 0) * 100) / 100;
                };
                const computePension = (basicSalary, applicable, isEmployer = false) => {
                    const config = pensionConfig.value || {};
                    if (!applicable || !(config.enabled ?? true)) return 0;
                    const base = Math.max(parseFloat(basicSalary) || 0, 0);
                    const rate = isEmployer ? Number(config.employer_rate || 0) : Number(config.employee_rate || 0);
                    return Math.round(base * rate * 100) / 100;
                };
                const rowPaye = (row) => computePaye(rowGross(row), !!row.paye);
                const rowEmployeePension = (row) => computePension(row.basic_salary, !!row.pension_applicable, false);
                const rowEmployerPension = (row) => computePension(row.basic_salary, !!row.pension_applicable, true);
                const rowNet = (row) => Math.round((rowGross(row) - (parseFloat(row.deductions) || 0) - rowPaye(row) - rowEmployeePension(row)) * 100) / 100;
                const totalPaye = computed(() => selectedRows.value.reduce((sum, row) => sum + rowPaye(row), 0));
                const totalEmployeePension = computed(() => selectedRows.value.reduce((sum, row) => sum + rowEmployeePension(row), 0));
                const totalNet = computed(() => selectedRows.value.reduce((sum, row) => sum + rowNet(row), 0));

                const toggleSelectAll = () => {
                    const select = !allSelected.value;
                    employeeRows.value.forEach((row) => { row.selected = select; });
                };

                const loadEmployees = async () => {
                    if (employeeRows.value.length || employeesLoading.value) return;
                    employeesLoading.value = true;
                    try {
                        const { data } = await axios.get(@json(route('payroll.employees')));
                        employeeRows.value = data.map((employee) => {
                            const [type, id] = employee.value.split(':');
                            return {
                                key: employee.value,
                                id,
                                type: type === 'instructor' ? 'Instructor' : 'Administrator',
                                name: employee.name || employee.label.split(/\\s[\\u2014\\u2013-]\\s/)[0],
                                selected: false,
                                default_salary: employee.basic_salary !== null ? Number(employee.basic_salary) : null,
                                basic_salary: employee.basic_salary !== null ? Number(employee.basic_salary) : null,
                                allowances: null,
                                deductions: null,
                                paye: !!employee.paye,
                                pension_applicable: !!employee.pension_applicable
                            };
                        });
                    } catch (error) {
                        groupForm.value.error = 'Unable to load the employee list. Close and reopen the form to retry.';
                    } finally {
                        employeesLoading.value = false;
                    }
                };

                const loadBonusPreviews = async () => {
                    bonuses.value = { rate: 0, attendances: {} };
                    if (!groupForm.value.payment_month) return;
                    try {
                        const { data } = await axios.get(@json(route('payroll.bonus-previews')), {
                            params: { payment_month: groupForm.value.payment_month }
                        });
                        bonuses.value = data;
                    } catch (error) {
                        // Bonuses are recomputed server-side on save; the preview is best-effort.
                    }
                };

                const groupModalMode = ref('add'); // add | edit | review
                const groupReviewFinalizing = ref(false);

                const openAddPayroll = () => {
                    groupModalMode.value = 'add';
                    groupForm.value = emptyGroupForm();
                    bonuses.value = { rate: 0, attendances: {} };
                    employeeRows.value.forEach((row) => {
                        row.selected = false;
                        row.basic_salary = row.default_salary ?? null;
                        row.allowances = null;
                        row.deductions = null;
                    });
                    loadEmployees();
                    getModal('payroll-add-modal')?.show();
                    setTimeout(() => {
                        initGroupMonthPicker();
                        syncGroupMonthPicker();
                    }, 0);
                };

                const saveGroup = async () => {
                    if (groupForm.value.saving || groupReviewFinalizing.value) return;
                    if (!selectedCount.value) {
                        groupForm.value.error = 'Select at least one employee.';
                        return;
                    }
                    groupForm.value.saving = true;
                    groupForm.value.error = '';
                    try {
                        const payload = {
                            payment_month: groupForm.value.payment_month,
                            group_name: groupForm.value.name || null,
                            employees: selectedRows.value.map((row) => ({
                                employee: row.key,
                                basic_salary: row.basic_salary || 0,
                                allowances: row.allowances || 0,
                                deductions: row.deductions || 0
                            }))
                        };
                        const response = groupForm.value.id
                            ? await axios.put(@json(url('hrm/payroll/groups')) + '/' + groupForm.value.id, payload)
                            : await axios.post(@json(route('payroll.store')), payload);
                        getModal('payroll-add-modal')?.hide();
                        toast(response.data.message);
                        reloadTable();
                        reloadGroups();
                    } catch (error) {
                        groupForm.value.error = error.response?.data?.message
                            || (groupForm.value.id ? 'Unable to update the payroll group. Please try again.' : 'Unable to create the payroll group. Please try again.');
                    } finally {
                        groupForm.value.saving = false;
                    }
                };

                const loadGroupIntoAddModal = async (group, mode) => {
                    groupModalMode.value = mode;
                    groupForm.value = { ...emptyGroupForm(), id: group.id, name: group.name || '' };
                    employeeRows.value = [];
                    bonuses.value = { rate: 0, attendances: {} };
                    groupReviewFinalizing.value = false;
                    getModal('payroll-add-modal')?.show();
                    setTimeout(() => {
                        initGroupMonthPicker();
                        syncGroupMonthPicker();
                    }, 0);

                    try {
                        const { data } = await axios.get(@json(url('hrm/payroll/groups')) + '/' + group.id);
                        groupForm.value.payment_month = data.group.payment_month;
                        if (data.group.name) groupForm.value.name = data.group.name;
                        syncGroupMonthPicker();

                        await loadEmployees();
                        if (groupForm.value.error) throw new Error(groupForm.value.error);

                        data.records.forEach((record) => {
                            let row = employeeRows.value.find((employee) => employee.key === record.employee_key);
                            if (!row) {
                                const [type, id] = record.employee_key.split(':');
                                row = {
                                    key: record.employee_key,
                                    id,
                                    type: type === 'instructor' ? 'Instructor' : 'Administrator',
                                    name: record.employee,
                                    selected: false,
                                    default_salary: record.basic_salary,
                                    basic_salary: record.basic_salary,
                                    allowances: 0,
                                    deductions: 0,
                                    paye: Number(record.paye || 0) > 0,
                                    pension_applicable: !!record.pension_applicable
                                };
                                employeeRows.value.push(row);
                            }
                            row.selected = true;
                            row.basic_salary = record.basic_salary;
                            row.allowances = record.allowances;
                            row.deductions = record.deductions;
                            row.paye = Number(record.paye || 0) > 0 || !!row.paye;
                            row.pension_applicable = !!record.pension_applicable || !!row.pension_applicable;
                        });

                        await loadBonusPreviews();
                    } catch (error) {
                        const fallback = mode === 'review'
                            ? 'Unable to load the payroll group for review.'
                            : 'Unable to load the pending payroll group for editing.';
                        groupForm.value.error = error.response?.data?.message || error.message || fallback;
                    }
                };

                const finalizeGroupFromForm = async () => {
                    if (!groupForm.value.id || groupReviewFinalizing.value || groupForm.value.saving || groupModalMode.value !== 'review') return;
                    if (!selectedCount.value) {
                        groupForm.value.error = 'Select at least one employee.';
                        return;
                    }
                    groupReviewFinalizing.value = true;
                    groupForm.value.error = '';
                    try {
                        const payload = {
                            payment_month: groupForm.value.payment_month,
                            group_name: groupForm.value.name || null,
                            employees: selectedRows.value.map((row) => ({
                                employee: row.key,
                                basic_salary: row.basic_salary || 0,
                                allowances: row.allowances || 0,
                                deductions: row.deductions || 0
                            }))
                        };
                        await axios.put(@json(url('hrm/payroll/groups')) + '/' + groupForm.value.id, payload);
                        const { data } = await axios.post(
                            @json(url('hrm/payroll/groups')) + '/' + groupForm.value.id + '/finalize'
                        );
                        getModal('payroll-add-modal')?.hide();
                        toast(data.message);
                        reloadGroups();
                        reloadTable();
                    } catch (error) {
                        const message = error.response?.data?.message || 'Unable to save and finalize the payroll group. Please try again.';
                        groupForm.value.error = message;
                        Swal.fire({
                            icon: 'error',
                            title: 'Unable to approve and finalize group',
                            text: message
                        });
                    } finally {
                        groupReviewFinalizing.value = false;
                    }
                };

                // ----- Groups tab -----
                const reloadGroups = () => groupsTable?.ajax.reload(null, false);
                const groupDetails = ref(null);
                const groupDetailsLoading = ref(false);
                const groupDetailsError = ref('');
                const groupReviewMode = ref(false);
                const groupFinalizeSaving = ref(false);

                const canFinalizeLoadedGroup = computed(() => {
                    if (!groupReviewMode.value || !groupDetails.value) return false;
                    const summary = groupDetails.value.summary || {};
                    return Number(summary.employees || 0) > 0 && Number(summary.pending || 0) === Number(summary.employees || 0);
                });

                const requestPaymentDate = async (title) => {
                    const today = new Date();
                    const paymentDate = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
                    const result = await Swal.fire({
                        title,
                        text: 'Select the payment date.',
                        input: 'date',
                        inputValue: paymentDate,
                        showCancelButton: true,
                        confirmButtonText: 'Pay',
                        inputValidator: (value) => !value && 'Select a payment date.'
                    });

                    return result.isConfirmed ? result.value : null;
                };

                window.payPayrollGroup = async (element) => {
                    const group = JSON.parse(element.dataset.group);
                    const paymentDate = await requestPaymentDate(`Pay "${group.name}"?`);
                    if (!paymentDate) return;

                    try {
                        const { data } = await axios.post(
                            @json(url('hrm/payroll/groups')) + '/' + group.id + '/pay',
                            { payment_date: paymentDate }
                        );
                        toast(data.message);
                        reloadGroups();
                        reloadTable();
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Unable to pay group',
                            text: error.response?.data?.message || 'Please try again.'
                        });
                    }
                };

                const finalizeLoadedPayrollGroup = async () => {
                    if (!groupDetails.value || groupFinalizeSaving.value) return;
                    groupFinalizeSaving.value = true;
                    try {
                        const { data } = await axios.post(
                            @json(url('hrm/payroll/groups')) + '/' + groupDetails.value.group.id + '/finalize'
                        );
                        getModal('payroll-group-modal')?.hide();
                        toast(data.message);
                        reloadGroups();
                        reloadTable();
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Unable to finalize group',
                            text: error.response?.data?.message || 'Please try again.'
                        });
                    } finally {
                        groupFinalizeSaving.value = false;
                    }
                };

                window.editPayrollGroup = async (element) => {
                    const group = JSON.parse(element.dataset.group);
                    await loadGroupIntoAddModal(group, 'edit');
                };

                const loadPayrollGroupDetails = async (group, reviewMode = false) => {
                    groupReviewMode.value = !!reviewMode;
                    groupDetails.value = null;
                    groupDetailsError.value = '';
                    groupDetailsLoading.value = true;
                    getModal('payroll-group-modal')?.show();
                    try {
                        const { data } = await axios.get(@json(url('hrm/payroll/groups')) + '/' + group.id);
                        groupDetails.value = data;
                    } catch (error) {
                        groupDetailsError.value = 'Unable to load the payroll group details. Please try again.';
                    } finally {
                        groupDetailsLoading.value = false;
                    }
                };

                window.viewPayrollGroup = async (element) => {
                    const group = JSON.parse(element.dataset.group);
                    await loadPayrollGroupDetails(group, false);
                };

                window.reviewPayrollGroup = async (element) => {
                    const group = JSON.parse(element.dataset.group);
                    await loadGroupIntoAddModal(group, 'review');
                };

                const filterByGroup = () => {
                    if (!groupDetails.value) return;
                    groupFilter.value = { id: groupDetails.value.group.id, name: groupDetails.value.group.name };
                    getModal('payroll-group-modal')?.hide();
                    bootstrap.Tab.getOrCreateInstance(document.getElementById('payrolls-tab')).show();
                    reloadTable();
                };

                const clearGroupFilter = () => {
                    groupFilter.value = null;
                    reloadTable();
                };

                window.deletePayrollGroup = (element) => {
                    const group = JSON.parse(element.dataset.group);
                    Swal.fire({
                        title: 'Delete payroll group?',
                        text: `"${group.name}" and its ${group.count} payroll record(s) will be removed.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Delete'
                    }).then(async (result) => {
                        if (!result.isConfirmed) return;
                        try {
                            const { data } = await axios.delete(@json(url('hrm/payroll/groups')) + '/' + group.id);
                            toast(data.message);
                            if (groupFilter.value?.id === group.id) groupFilter.value = null;
                            reloadGroups();
                            reloadTable();
                        } catch (error) {
                            Swal.fire({ icon: 'error', title: 'Unable to delete', text: error.response?.data?.message || 'Please try again.' });
                        }
                    });
                };


                window.payPayroll = async (element) => {
                    const payroll = JSON.parse(element.dataset.payroll);
                    const paymentDate = await requestPaymentDate(`Pay ${payroll.employee}?`);
                    if (!paymentDate) return;

                    try {
                        const { data } = await axios.post(
                            @json(url('hrm/payroll')) + '/' + payroll.id + '/pay',
                            { payment_date: paymentDate }
                        );
                        toast(data.message);
                        reloadTable();
                        reloadGroups();
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Unable to pay payroll',
                            text: error.response?.data?.message || 'Please try again.'
                        });
                    }
                };



                const loadPayrollSummary = async () => {
                    payrollSummaryLoading.value = true;
                    try {
                        const { data } = await axios.get(@json(route('payroll.summary')), {
                            params: { ...monthFilterParams(), group: groupFilter.value?.id || '' }
                        });
                        payrollSummary.value = { ...payrollSummary.value, ...data };
                    } catch (error) {
                        // Keep existing summary values if refresh fails.
                    } finally {
                        payrollSummaryLoading.value = false;
                    }
                };

                const reloadTable = () => {
                    table?.ajax.reload(null, false);
                    loadPayrollSummary();
                };
                const clearMonthFilter = () => {
                    monthFilter.value = '';
                    monthFrom.value = '';
                    monthTo.value = '';
                    syncMonthFilterPickers();
                    reloadTable();
                };
                @endcan

                onMounted(() => {
                    axios.defaults.headers.common['X-CSRF-TOKEN'] = $('meta[name="csrf-token"]').attr('content');
                    @can('view payroll')
                    initMonthFilterPickers();
                    syncMonthFilterPickers();
                    loadPayrollSummary();
                    if (document.getElementById('payrollTable')) {
                        $('#payrolls-tab').on('shown.bs.tab', () => table?.columns.adjust());
                        $('#groups-tab').on('shown.bs.tab', () => groupsTable?.columns.adjust());
                        groupsTable = $('#groupsTable').DataTable({
                            serverSide: true,
                            processing: true,
                            scrollX: true,
                            scrollCollapse: true,
                            pageLength: 25,
                            lengthMenu: [10, 25, 50, 100],
                            order: [[5, 'desc']],
                            ajax: async (data, callback) => {
                                try {
                                    const response = await axios.get(@json(route('payroll.groups')), {
                                        params: data,
                                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                                    });
                                    callback(response.data);
                                } catch (error) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Unable to load payroll groups',
                                        text: [401, 403].includes(error.response?.status)
                                            ? 'Session expired or access denied. Reload the page.'
                                            : 'Please try again.'
                                    });
                                }
                            },
                            columns: [
                                { data: 'group', name: 'name' },
                                { data: 'month', name: 'payment_month' },
                                { data: 'employees', name: 'payrolls_count', className: 'text-end' },
                                { data: 'total_net', name: 'payrolls_sum_net_pay', className: 'text-end' },
                                { data: 'payment_status', name: 'paid_count' },
                                { data: 'created_by', orderable: false, searchable: false },
                                { data: 'created_on', name: 'created_at' },
                                { data: 'actions', className: 'text-center', orderable: false, searchable: false }
                            ]
                        });
                        table = $('#payrollTable').DataTable({
                            serverSide: true,
                            processing: true,
                            scrollX: true,
                            scrollCollapse: true,
                            pageLength: 25,
                            lengthMenu: [10, 25, 50, 100],
                            order: [[3, 'desc']],
                            ajax: async (data, callback) => {
                                try {
                                    NProgress.start();
                                    const response = await axios.get(@json(route('payroll.fetch')), {
                                        params: { ...data, ...monthFilterParams(), group: groupFilter.value?.id || '' },
                                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                                    });
                                    callback(response.data);
                                } catch (error) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Unable to load payroll records',
                                        text: [401, 403].includes(error.response?.status)
                                            ? 'Session expired or access denied. Reload the page.'
                                            : 'Please try again.'
                                    });
                                } finally {
                                    NProgress.done();
                                }
                            },
                            columns: [
                                { data: 'actions', className: 'text-center', orderable: false, searchable: false },
                                { data: 'employee', orderable: false },
                                { data: 'type', orderable: false, searchable: false },
                                { data: 'month', name: 'payment_month' },
                                { data: 'basic_salary', name: 'basic_salary', className: 'text-end' },
                                { data: 'allowances', name: 'allowances', className: 'text-end' },
                                { data: 'bonus', name: 'bonus', className: 'text-end' },
                                { data: 'deductions', name: 'deductions', className: 'text-end' },
                                { data: 'net_pay', name: 'net_pay', className: 'text-end' },
                                { data: 'payment_date', name: 'payment_date' },
                                { data: 'status', name: 'status' },
                                { data: 'processed_by', orderable: false, searchable: false }
                            ]
                        });
                    }
                    @endcan
                });

                return {
                    @can('view payroll')
                    monthFilter, monthFrom, monthTo, monthFilterMode, hasMonthFilter, onMonthFilterModeChange,
                    groupFilter, payrollSummary, payrollSummaryLoading, formatNumber, loadPayrollSummary,
                    employeeRows, employeesLoading, bonuses, groupForm,
                    selectedCount, allSelected, defaultGroupName, totalNet, totalEmployeePension,
                    rowAttendances, rowBonus, rowPaye, rowEmployeePension, rowNet, totalPaye, toggleSelectAll,
                    loadBonusPreviews, openAddPayroll, saveGroup,
                    reloadGroups, clearGroupFilter,
                    groupDetails, groupDetailsLoading, groupDetailsError, filterByGroup,
                    groupModalMode, groupReviewFinalizing, finalizeGroupFromForm,
                    formatAmount, formatMonth,
                    reloadTable, clearMonthFilter,
                    @endcan
                };
            }
        }).mount('#payroll');
    </script>
@endsection
