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
                @can('view salary grades')
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ auth()->user()->can('view payroll') ? '' : 'active' }}"
                                id="salary-grades-tab" data-bs-toggle="tab" data-bs-target="#salary-grades-panel"
                                type="button" role="tab" aria-controls="salary-grades-panel"
                                aria-selected="{{ auth()->user()->can('view payroll') ? 'false' : 'true' }}">
                            <i class="fa fa-layer-group me-2"></i>Salary Grades
                        </button>
                    </li>
                @endcan
            </ul>
            <div class="block-content tab-content">
                @can('view payroll')
                <div class="tab-pane fade show active" id="payrolls-panel" role="tabpanel" aria-labelledby="payrolls-tab">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <label for="payroll-month-filter" class="fs-sm text-muted mb-0">Month</label>
                        <input type="month" id="payroll-month-filter" class="form-control form-control-sm w-auto" v-model="monthFilter" @change="reloadTable">
                        <button type="button" class="btn btn-sm btn-outline-secondary" v-if="monthFilter" @click="clearMonthFilter">Clear</button>
                        <span v-if="groupFilter" class="badge bg-primary d-inline-flex align-items-center gap-2">
                            Group: @{{ groupFilter.name }}
                            <button type="button" class="btn-close btn-close-white" style="font-size: .6rem;" aria-label="Clear group filter" @click="clearGroupFilter"></button>
                        </span>
                    </div>
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
                @can('view salary grades')
                    @include('hrm.partials.salary-grades-tab')
                @endcan
            </div>
        </div>

        @can('view payroll')
        @can('process payroll')
        {{-- Add payroll (group / multi-employee) modal --}}
        <div class="modal fade" id="payroll-add-modal" tabindex="-1" aria-labelledby="payroll-add-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-white" id="payroll-add-modal-title">Add payroll</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form @submit.prevent="saveGroup">
                        <div class="modal-body" style="max-height: calc(100vh - 230px); overflow-y: auto;">
                            <div v-if="groupForm.error" class="alert alert-danger" role="alert">@{{ groupForm.error }}</div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label" for="group-month">Pay month</label>
                                    <input id="group-month" type="month" class="form-control" v-model="groupForm.payment_month" required @change="loadBonusPreviews">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="group-name">Group name <span class="text-muted fs-sm">(optional)</span></label>
                                    <input id="group-name" type="text" class="form-control" v-model="groupForm.name" maxlength="255" :placeholder="defaultGroupName">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="group-status">Status</label>
                                    <select id="group-status" class="form-select" v-model="groupForm.status" required>
                                        <option value="Pending">Pending</option>
                                        <option value="Paid">Paid</option>
                                    </select>
                                </div>
                                <div class="col-md-2" v-if="groupForm.status === 'Paid'">
                                    <label class="form-label" for="group-date">Payment date</label>
                                    <input id="group-date" type="date" class="form-control" v-model="groupForm.payment_date" required>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-end gap-2 mb-2">
                                <div>
                                    <label class="form-label mb-1 fs-sm" for="group-default-salary">Default basic salary (K)</label>
                                    <input id="group-default-salary" type="number" step="0.01" min="0" class="form-control form-control-sm" v-model.number="defaultSalary" style="width: 160px;">
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="applyDefaultSalary" :disabled="!selectedCount">Apply to selected</button>
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
                                            <th class="text-end">Net pay</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="!employeeRows.length">
                                            <td colspan="8" class="text-center text-muted py-3">
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
                                    <span class="text-muted fs-sm d-block">Total net pay (@{{ selectedCount }} selected)</span>
                                    <span class="fw-semibold fs-5">K@{{ formatAmount(totalNet) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="groupForm.saving || !selectedCount">
                                <span v-if="groupForm.saving" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                Create payroll (@{{ selectedCount }})
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Edit single payroll record modal --}}
        <div class="modal fade" id="payroll-modal" tabindex="-1" aria-labelledby="payroll-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-white" id="payroll-modal-title">Edit payroll record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form @submit.prevent="savePayroll">
                        <div class="modal-body">
                            <div v-if="formError" class="alert alert-danger" role="alert">@{{ formError }}</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Employee</label>
                                    <input type="text" class="form-control-plaintext" :value="form.employeeName" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Pay month</label>
                                    <input type="text" class="form-control-plaintext" :value="form.payment_month" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="payroll-basic">Basic salary (K)</label>
                                    <input id="payroll-basic" type="number" step="0.01" min="0" class="form-control" v-model.number="form.basic_salary" required>
                                    <div v-if="formErrors.basic_salary" class="text-danger fs-sm">@{{ formErrors.basic_salary[0] }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="payroll-allowances">Allowances (K)</label>
                                    <input id="payroll-allowances" type="number" step="0.01" min="0" class="form-control" v-model.number="form.allowances">
                                    <div v-if="formErrors.allowances" class="text-danger fs-sm">@{{ formErrors.allowances[0] }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="payroll-deductions">Deductions (K)</label>
                                    <input id="payroll-deductions" type="number" step="0.01" min="0" class="form-control" v-model.number="form.deductions">
                                    <div v-if="formErrors.deductions" class="text-danger fs-sm">@{{ formErrors.deductions[0] }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label" for="payroll-status">Status</label>
                                    <select id="payroll-status" class="form-select" v-model="form.status" required>
                                        <option value="Pending">Pending</option>
                                        <option value="Paid">Paid</option>
                                    </select>
                                    <div v-if="formErrors.status" class="text-danger fs-sm">@{{ formErrors.status[0] }}</div>
                                </div>
                                <div class="col-md-4" v-if="form.status === 'Paid'">
                                    <label class="form-label" for="payroll-date">Payment date</label>
                                    <input id="payroll-date" type="date" class="form-control" v-model="form.payment_date" required>
                                    <div v-if="formErrors.payment_date" class="text-danger fs-sm">@{{ formErrors.payment_date[0] }}</div>
                                </div>
                            </div>
                            <div class="bg-body-light rounded-3 p-3 mt-3">
                                <div class="d-flex flex-wrap gap-4">
                                    <div v-if="isInstructorSelected">
                                        <span class="text-muted fs-sm d-block">Instructor bonus (auto-calculated)</span>
                                        <span class="fw-semibold" v-if="bonusLoading"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                                        <span class="fw-semibold" v-else>K@{{ formatAmount(bonus.bonus) }} <small class="text-muted">(@{{ bonus.attendances }} attendances &times; K@{{ formatAmount(bonus.rate) }})</small></span>
                                    </div>
                                    <div>
                                        <span class="text-muted fs-sm d-block">Net pay</span>
                                        <span class="fw-semibold fs-5">K@{{ formatAmount(netPay) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                Save changes
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
                    <div class="modal-header">
                        <h5 class="modal-title text-white" id="payroll-group-modal-title">@{{ groupDetails?.group.name || 'Payroll group' }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                        <small class="text-muted d-block">@{{ groupDetails.summary.paid }} paid / @{{ groupDetails.summary.pending }} pending</small>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-lg-3">
                                    <div class="bg-body-light rounded-3 p-3 h-100">
                                        <span class="text-muted fs-sm d-block">Total net pay</span>
                                        <span class="fw-semibold fs-5">K@{{ formatAmount(groupDetails.summary.net_pay) }}</span>
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
                                            <td class="text-end fw-semibold">K@{{ formatAmount(record.net_pay) }}</td>
                                            <td>@{{ record.payment_date || '-' }}</td>
                                            <td>
                                                <span class="badge" :class="record.status === 'Paid' ? 'bg-success' : 'bg-warning text-dark'">@{{ record.status }}</span>
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
                const monthFilter = ref('');
                const groupFilter = ref(null);

                const employeeRows = ref([]);
                const employeesLoading = ref(false);
                const bonuses = ref({ rate: 0, attendances: {} });
                const defaultSalary = ref(null);
                const groupForm = ref(emptyGroupForm());

                const form = ref(emptyForm());
                const formErrors = ref({});
                const formError = ref('');
                const saving = ref(false);
                const bonus = ref({ attendances: 0, rate: 0, bonus: 0 });
                const bonusLoading = ref(false);
                let table = null;
                let groupsTable = null;
                @endcan

                @can('view salary grades')
                const salaryGrades = ref([]);
                const salaryGradesLoading = ref(true);
                const salaryGradesLoadError = ref('');
                const salaryGradeForm = ref(emptySalaryGradeForm());
                const salaryGradeFormErrors = ref({});
                const salaryGradeFormError = ref('');
                const salaryGradeSaving = ref(false);
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
                    return { payment_month: '', name: '', status: 'Pending', payment_date: '', saving: false, error: '' };
                }

                function emptyForm() {
                    return {
                        id: null, employee: '', employeeName: '', payment_month: '',
                        basic_salary: null, allowances: null, deductions: null,
                        status: 'Pending', payment_date: ''
                    };
                }
                @endcan

                @can('view salary grades')
                function emptySalaryGradeForm() {
                    return { id: null, code: '', name: '', description: '', basic_salary: null };
                }
                @endcan

                @can('view payroll')
                const formatAmount = (value) => Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const formatMonth = (month) => {
                    if (!month) return '-';
                    const [year, monthNumber] = month.split('-');
                    return new Date(Number(year), Number(monthNumber) - 1, 1).toLocaleString('en-GB', { month: 'long', year: 'numeric' });
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
                const rowNet = (row) => Math.round((
                    (parseFloat(row.basic_salary) || 0)
                    + (parseFloat(row.allowances) || 0)
                    + rowBonus(row)
                    - (parseFloat(row.deductions) || 0)
                ) * 100) / 100;
                const totalNet = computed(() => selectedRows.value.reduce((sum, row) => sum + rowNet(row), 0));

                const toggleSelectAll = () => {
                    const select = !allSelected.value;
                    employeeRows.value.forEach((row) => { row.selected = select; });
                };

                const applyDefaultSalary = () => {
                    if (defaultSalary.value === null || defaultSalary.value === '') return;
                    selectedRows.value.forEach((row) => { row.basic_salary = defaultSalary.value; });
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
                                name: employee.label.split(' — ')[0],
                                selected: false,
                                default_salary: employee.basic_salary !== null ? Number(employee.basic_salary) : null,
                                basic_salary: employee.basic_salary !== null ? Number(employee.basic_salary) : null,
                                allowances: null,
                                deductions: null
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

                const openAddPayroll = () => {
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
                };

                const saveGroup = async () => {
                    if (groupForm.value.saving) return;
                    if (!selectedCount.value) {
                        groupForm.value.error = 'Select at least one employee.';
                        return;
                    }
                    groupForm.value.saving = true;
                    groupForm.value.error = '';
                    try {
                        const response = await axios.post(@json(route('payroll.store')), {
                            payment_month: groupForm.value.payment_month,
                            group_name: groupForm.value.name || null,
                            status: groupForm.value.status,
                            payment_date: groupForm.value.status === 'Paid' ? groupForm.value.payment_date : null,
                            employees: selectedRows.value.map((row) => ({
                                employee: row.key,
                                basic_salary: row.basic_salary || 0,
                                allowances: row.allowances || 0,
                                deductions: row.deductions || 0
                            }))
                        });
                        getModal('payroll-add-modal')?.hide();
                        toast(response.data.message);
                        reloadTable();
                        reloadGroups();
                    } catch (error) {
                        groupForm.value.error = error.response?.data?.message || 'Unable to create the payroll group. Please try again.';
                    } finally {
                        groupForm.value.saving = false;
                    }
                };

                // ----- Groups tab -----
                const reloadGroups = () => groupsTable?.ajax.reload(null, false);
                const groupDetails = ref(null);
                const groupDetailsLoading = ref(false);
                const groupDetailsError = ref('');

                window.viewPayrollGroup = async (element) => {
                    const group = JSON.parse(element.dataset.group);
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

                // ----- Edit single record -----
                const isInstructorSelected = computed(() => (form.value.employee || '').startsWith('instructor:'));

                const netPay = computed(() => {
                    const basic = parseFloat(form.value.basic_salary) || 0;
                    const allowances = parseFloat(form.value.allowances) || 0;
                    const deductions = parseFloat(form.value.deductions) || 0;
                    return Math.round((basic + allowances + (parseFloat(bonus.value.bonus) || 0) - deductions) * 100) / 100;
                });

                const loadBonusPreview = async () => {
                    bonus.value = { attendances: 0, rate: 0, bonus: 0 };
                    if (!isInstructorSelected.value || !form.value.payment_month) return;
                    bonusLoading.value = true;
                    try {
                        const { data } = await axios.get(@json(route('payroll.bonus-preview')), {
                            params: { employee: form.value.employee, payment_month: form.value.payment_month }
                        });
                        bonus.value = data;
                    } catch (error) {
                        // Bonus is recomputed server-side on save; the preview is best-effort.
                    } finally {
                        bonusLoading.value = false;
                    }
                };

                window.openEditPayroll = (element) => {
                    const data = JSON.parse(element.dataset.payroll);
                    form.value = {
                        id: data.id,
                        employee: data.employee_key,
                        employeeName: data.employee,
                        payment_month: data.payment_month,
                        basic_salary: data.basic_salary,
                        allowances: data.allowances,
                        deductions: data.deductions,
                        status: data.status,
                        payment_date: data.payment_date || ''
                    };
                    formErrors.value = {};
                    formError.value = '';
                    loadBonusPreview();
                    getModal('payroll-modal')?.show();
                };

                window.openDeletePayroll = (id) => {
                    Swal.fire({
                        title: 'Delete payroll record?',
                        text: 'This payroll record will be removed.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Delete'
                    }).then(async (result) => {
                        if (!result.isConfirmed) return;
                        try {
                            const { data } = await axios.delete(@json(url('hrm/payroll')) + '/' + id);
                            toast(data.message);
                            reloadTable();
                            reloadGroups();
                        } catch (error) {
                            Swal.fire({ icon: 'error', title: 'Unable to delete', text: error.response?.data?.message || 'Please try again.' });
                        }
                    });
                };

                const savePayroll = async () => {
                    if (saving.value) return;
                    saving.value = true;
                    formErrors.value = {};
                    formError.value = '';
                    try {
                        const response = await axios.put(@json(url('hrm/payroll')) + '/' + form.value.id, {
                            basic_salary: form.value.basic_salary,
                            allowances: form.value.allowances || 0,
                            deductions: form.value.deductions || 0,
                            status: form.value.status,
                            payment_date: form.value.status === 'Paid' ? form.value.payment_date : null
                        });
                        getModal('payroll-modal')?.hide();
                        toast(response.data.message);
                        reloadTable();
                        reloadGroups();
                    } catch (error) {
                        formErrors.value = error.response?.data?.errors ?? {};
                        formError.value = error.response?.data?.message || 'Unable to save the payroll record. Please try again.';
                    } finally {
                        saving.value = false;
                    }
                };

                const reloadTable = () => table?.ajax.reload(null, false);
                const clearMonthFilter = () => { monthFilter.value = ''; reloadTable(); };
                @endcan

                @can('view salary grades')
                const loadSalaryGrades = async () => {
                    salaryGradesLoading.value = true;
                    salaryGradesLoadError.value = '';
                    try {
                        const { data } = await axios.get(@json(route('salary-grades.fetch')));
                        salaryGrades.value = data;
                    } catch (error) {
                        salaryGradesLoadError.value = 'Unable to load salary grades. Reload the page to try again.';
                    } finally {
                        salaryGradesLoading.value = false;
                    }
                };

                const openAddSalaryGrade = () => {
                    salaryGradeForm.value = emptySalaryGradeForm();
                    salaryGradeFormErrors.value = {};
                    salaryGradeFormError.value = '';
                    getModal('salary-grade-modal')?.show();
                };

                const openEditSalaryGrade = (item) => {
                    salaryGradeForm.value = {
                        id: item.id,
                        code: item.code,
                        name: item.name,
                        description: item.description || '',
                        basic_salary: Number(item.basic_salary)
                    };
                    salaryGradeFormErrors.value = {};
                    salaryGradeFormError.value = '';
                    getModal('salary-grade-modal')?.show();
                };

                const saveSalaryGrade = async () => {
                    if (salaryGradeSaving.value) return;
                    salaryGradeSaving.value = true;
                    salaryGradeFormErrors.value = {};
                    salaryGradeFormError.value = '';
                    try {
                        const payload = {
                            code: salaryGradeForm.value.code,
                            name: salaryGradeForm.value.name,
                            description: salaryGradeForm.value.description || null,
                            basic_salary: salaryGradeForm.value.basic_salary
                        };
                        const response = salaryGradeForm.value.id
                            ? await axios.put(@json(url('hrm/salary-grades')) + '/' + salaryGradeForm.value.id, payload)
                            : await axios.post(@json(route('salary-grades.store')), payload);
                        getModal('salary-grade-modal')?.hide();
                        toast(response.data.message);
                        loadSalaryGrades();
                    } catch (error) {
                        salaryGradeFormErrors.value = error.response?.data?.errors ?? {};
                        salaryGradeFormError.value = error.response?.data?.message || 'Unable to save the salary grade. Please try again.';
                    } finally {
                        salaryGradeSaving.value = false;
                    }
                };

                const confirmDeleteSalaryGrade = (item) => {
                    Swal.fire({
                        title: 'Delete salary grade?',
                        text: `"${item.code} - ${item.name}" will be removed.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Delete'
                    }).then(async (result) => {
                        if (!result.isConfirmed) return;
                        try {
                            const { data } = await axios.delete(@json(url('hrm/salary-grades')) + '/' + item.id);
                            toast(data.message);
                            loadSalaryGrades();
                        } catch (error) {
                            Swal.fire({ icon: 'error', title: 'Unable to delete', text: error.response?.data?.message || 'Please try again.' });
                        }
                    });
                };

                const formatSalaryAmount = (value) => Number(value || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                @endcan

                onMounted(() => {
                    axios.defaults.headers.common['X-CSRF-TOKEN'] = $('meta[name="csrf-token"]').attr('content');
                    @can('view payroll')
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
                                        params: { ...data, month: monthFilter.value, group: groupFilter.value?.id || '' },
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
                    @can('view salary grades')
                    if (document.getElementById('salary-grades-panel')) {
                        loadSalaryGrades();
                    }
                    @endcan
                });

                return {
                    @can('view payroll')
                    monthFilter, groupFilter,
                    employeeRows, employeesLoading, bonuses, defaultSalary, groupForm,
                    selectedCount, allSelected, defaultGroupName, totalNet,
                    rowAttendances, rowBonus, rowNet, toggleSelectAll, applyDefaultSalary,
                    loadBonusPreviews, openAddPayroll, saveGroup,
                    reloadGroups, clearGroupFilter,
                    groupDetails, groupDetailsLoading, groupDetailsError, filterByGroup,
                    form, formErrors, formError, saving, bonus, bonusLoading,
                    isInstructorSelected, netPay, formatAmount, formatMonth,
                    loadBonusPreview, savePayroll, reloadTable, clearMonthFilter,
                    @endcan
                    @can('view salary grades')
                    salaryGrades, salaryGradesLoading, salaryGradesLoadError,
                    salaryGradeForm, salaryGradeFormErrors, salaryGradeFormError, salaryGradeSaving,
                    openAddSalaryGrade, openEditSalaryGrade, saveSalaryGrade,
                    confirmDeleteSalaryGrade, formatSalaryAmount
                    @endcan
                };
            }
        }).mount('#payroll');
    </script>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            if (window.location.hash === '#salary-grades-tab') {
                const salaryGradesTab = document.getElementById('salary-grades-tab');
                if (salaryGradesTab) {
                    bootstrap.Tab.getOrCreateInstance(salaryGradesTab).show();
                }
            }
        });
    </script>
@endsection
