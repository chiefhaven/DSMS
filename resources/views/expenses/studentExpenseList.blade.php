@extends('layouts.backend')

@section('content')
<!-- Hero -->
<div id="studentExpenseList">
    <div class="bg-body-light">
        <div class="content content-full">
            <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
                <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">
                    Payable Expenses for @{{ student.fname }} @{{ student.sname }}
                </h1>
                <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">App</li>
                        <li class="breadcrumb-item active" aria-current="page">Expenses</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <!-- END Hero -->

    <div class="content">
        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h3 class="block-title">Expense List</h3>
                <div class="block-options">
                    <button @click="refreshData" class="btn btn-sm btn-alt-primary">
                        <i class="fa fa-refresh me-1"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="block-content">
                <div v-if="loading" class="text-center py-4">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Loading expenses...</p>
                </div>

                <div v-else-if="student && student.expenses.length > 0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Group</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>List Status</th>
                                    <th>Payment Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(expense, index) in filteredExpenses" :key="expense.id">
                                    <td>@{{ index + 1 }}</td>
                                    <td>@{{ expense.group }}</td>
                                    <td>
                                        @{{ getExpenseTypeName(expense.pivot.expense_type) }}
                                        <span v-if="expense.pivot?.repeat === 1" class="badge bg-danger ms-1">
                                            <small>Repeating</small>
                                        </span>
                                    </td>
                                    <td>K@{{ formatCurrency(expense.pivot?.amount) }}</td>
                                    <td>@{{ formatDate(expense.pivot?.due_date) }}</td>
                                    <td>
                                        <span v-if="expense.approved" class="badge bg-success">Approved</span>
                                        <span v-else class="badge bg-warning">Pending</span>
                                    </td>
                                    <td>
                                        <span v-if="expense.pivot?.status === 1" class="badge bg-success">Paid</span>
                                        <span v-else-if="isExpenseOverdue(expense)" class="badge bg-danger">Overdue</span>
                                        <span v-else class="badge bg-warning">Pending</span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button
                                                @click="loadPaymentForm(expense)"
                                                class="btn btn-sm btn-primary"
                                                :disabled="!isPaymentAllowed(expense)"
                                                v-tooltip="getPaymentTooltip(expense)"
                                            >
                                                <i class="fa fa-money-bill-wave me-1"></i> Pay
                                            </button>
                                            <button
                                                @click="viewPaymentHistory(expense)"
                                                class="btn btn-sm btn-info"
                                                v-tooltip="'View payment history'"
                                            >
                                                <i class="fa fa-history me-1"></i> History
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="totalAmount > 0">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Total:</td>
                                    <td class="fw-bold">K@{{ formatCurrency(totalAmount) }}</td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="btn-group">
                                <button
                                    @click="filter = 'all'"
                                    :class="['btn btn-sm', filter === 'all' ? 'btn-primary' : 'btn-outline-primary']"
                                >
                                    All (@{{ student.expenses.length }})
                                </button>
                                <button
                                    @click="filter = 'pending'"
                                    :class="['btn btn-sm', filter === 'pending' ? 'btn-primary' : 'btn-outline-primary']"
                                >
                                    Pending (@{{ pendingExpensesCount }})
                                </button>
                                <button
                                    @click="filter = 'paid'"
                                    :class="['btn btn-sm', filter === 'paid' ? 'btn-primary' : 'btn-outline-primary']"
                                >
                                    Paid (@{{ paidExpensesCount }})
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="alert alert-info">
                    <i class="fa fa-info-circle me-2"></i> No expenses found for this student.
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Make Payment</h5>
                        <button type="button" class="btn-close btn-close-white" @click="cancel" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form @submit.prevent="submitPayment">
                            <div class="mb-4">
                                <h5 class="text-center">@{{ selectedExpense.group }}</h5>
                                <p class="text-center text-muted">@{{ getExpenseOptionName(selectedExpense.pivot?.expense_type) }}</p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Amount Due (MWK)</label>
                                <input type="text" class="form-control" :value="'K' + formatCurrency(selectedExpense.pivot?.amount)" disabled />
                            </div>

                            <div class="mb-3">
                                <label for="amount" class="form-label">Amount Paying (MWK)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :max="selectedExpense.pivot?.amount"
                                    id="amount"
                                    class="form-control"
                                    v-model.number="form.amount"
                                    required
                                    @input="validateAmount"
                                />
                                <div v-if="amountError" class="text-danger small mt-1">@{{ amountError }}</div>
                            </div>

                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select id="payment_method" class="form-select" v-model="form.payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Mobile Money">Mobile Money</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>

                            <div class="mb-3" v-if="form.payment_method === 'Mobile Money'">
                                <label for="mobile_money_number" class="form-label">Mobile Money Number</label>
                                <input
                                    type="text"
                                    id="mobile_money_number"
                                    class="form-control"
                                    v-model="form.mobile_money_number"
                                    placeholder="e.g. 0881234567"
                                />
                            </div>

                            <div class="mb-3" v-if="form.payment_method === 'Bank Transfer'">
                                <label for="bank_reference" class="form-label">Bank Reference</label>
                                <input
                                    type="text"
                                    id="bank_reference"
                                    class="form-control"
                                    v-model="form.bank_reference"
                                    placeholder="Transaction reference"
                                />
                            </div>

                            <div class="mb-3">
                                <label for="payment_date" class="form-label">Payment Date</label>
                                <input
                                    type="date"
                                    id="payment_date"
                                    class="form-control"
                                    v-model="form.payment_date"
                                    :max="new Date().toISOString().split('T')[0]"
                                    required
                                />
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes (Optional)</label>
                                <textarea
                                    id="notes"
                                    class="form-control"
                                    v-model="form.notes"
                                    rows="2"
                                    placeholder="Any additional notes..."
                                ></textarea>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary me-2"
                                    @click="cancel"
                                    :disabled="isSubmitting"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    :disabled="isSubmitting || amountError"
                                >
                                    <span v-if="isSubmitting">
                                        <i class="fa fa-spinner fa-spin me-1"></i> Processing...
                                    </span>
                                    <span v-else>
                                        <i class="fa fa-check me-1"></i> Submit Payment
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History Modal -->
        <div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">Payment History</h5>
                        <button type="button" class="btn-close btn-close-white" @click="closeHistoryModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="paymentHistoryLoading" class="text-center py-4">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Loading payment history...</p>
                        </div>
                        <div v-else>
                            <div class="d-flex justify-content-between mb-3">
                                <h6>@{{ selectedExpense.group }} - @{{ getExpenseOptionName(selectedExpense.pivot?.expense_type) }}</h6>
                                <span class="badge bg-primary">Total Paid: K@{{ formatCurrency(totalPaidForExpense) }}</span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                            <th>Reference</th>
                                            <th>Received By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="payment in paymentHistory" :key="payment.id">
                                            <td>@{{ formatDateTime(payment.created_at) }}</td>
                                            <td>K@{{ formatCurrency(payment.amount) }}</td>
                                            <td>@{{ payment.payment_method }}</td>
                                            <td>@{{ payment.reference || 'N/A' }}</td>
                                            <td>@{{ payment.recorded_by?.name || 'System' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-if="paymentHistory.length === 0" class="alert alert-warning">
                                No payment history found for this expense.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script setup>
const app = Vue.createApp({
    setup() {
        const student = ref(@json($student));
        const loading = ref(false);
        const showPaymentForm = ref(false);
        const selectedExpense = ref({});
        const isSubmitting = ref(false);
        const paymentHistoryLoading = ref(false);
        const paymentHistory = ref([]);
        const filter = ref('all');
        const amountError = ref('');

        const form = reactive({
            amount: 0,
            payment_method: '',
            mobile_money_number: '',
            bank_reference: '',
            payment_date: new Date().toISOString().split('T')[0],
            notes: ''
        });

        // Expense types and lookup maps
        const expenseTypes = ref([]);
        const optionIdToOptionName = ref({});
        const optionIdToTypeName = ref({});

        // Computed properties
        const filteredExpenses = computed(() => {
            if (!student.value?.expenses) return [];

            return student.value.expenses.filter(expense => {
                if (filter.value === 'pending') {
                    return expense.pivot?.status !== 1;
                } else if (filter.value === 'paid') {
                    return expense.pivot?.status === 1;
                }
                return true;
            });
        });

        const pendingExpensesCount = computed(() => {
            return student.value?.expenses?.filter(e => e.pivot?.status !== 1).length || 0;
        });

        const paidExpensesCount = computed(() => {
            return student.value?.expenses?.filter(e => e.pivot?.status === 1).length || 0;
        });

        const totalAmount = computed(() => {
            return filteredExpenses.value.reduce((sum, expense) => sum + parseFloat(expense.pivot?.amount || 0), 0);
        });

        const totalPaidForExpense = computed(() => {
            return paymentHistory.value.reduce((sum, payment) => sum + parseFloat(payment.amount || 0), 0);
        });

        // Methods
        const formatCurrency = (value) => {
            return Number(value || 0).toLocaleString('en-MW', { minimumFractionDigits: 2 });
        };

        const formatDate = (dateString) => {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString();
        };

        const formatDateTime = (dateString) => {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        };

        const isExpenseOverdue = (expense) => {
            if (!expense.pivot?.due_date || expense.pivot?.status === 1) return false;
            return new Date(expense.pivot.due_date) < new Date();
        };

        const isPaymentAllowed = (expense) => {
            return expense.approved &&
                   expense.pivot?.status !== 1 &&
                   expense.pivot?.repeat !== 1;
        };

        const getPaymentTooltip = (expense) => {
            if (!expense.approved) return 'Expense is not approved yet';
            if (expense.pivot?.status === 1) return 'Already paid';
            if (expense.pivot?.repeat === 1) return 'Student is repeating this expense';
            return 'Make payment';
        };

        const validateAmount = () => {
            const maxAmount = parseFloat(selectedExpense.value.pivot?.amount || 0);
            if (form.amount > maxAmount) {
                amountError.value = `Amount cannot exceed K${formatCurrency(maxAmount)}`;
            } else {
                amountError.value = '';
            }
        };

        const getExpenseTypes = async () => {
            try {
                const res = await axios.get('/api/fetch-expense-types');
                expenseTypes.value = res.data;

                const optionNameMap = {};
                const typeNameMap = {};

                res.data.forEach(type => {
                    type.expense_type_options.forEach(opt => {
                        optionNameMap[opt.id] = opt.name;
                        typeNameMap[opt.id] = type.name;
                    });
                });

                optionIdToOptionName.value = optionNameMap;
                optionIdToTypeName.value = typeNameMap;
            } catch (error) {
                console.error('Failed to fetch expense types:', error);
                showAlert('Error', 'Failed to load expense types', 'error');
            }
        };

        const getExpenseOptionName = (id) => optionIdToOptionName.value[id] || '-';
        const getExpenseTypeName = (id) => optionIdToTypeName.value[id] || '-';

        const refreshData = async () => {
            loading.value = true;
            try {
                const response = await axios.get(`/api/students/${student.value.id}/expenses`);
                student.value = response.data;
                showAlert('Success', 'Data refreshed successfully', 'success', true);
            } catch (error) {
                console.error('Refresh error:', error);
                showAlert('Error', 'Failed to refresh data', 'error');
            } finally {
                loading.value = false;
            }
        };

        const loadPaymentForm = (expense) => {
            selectedExpense.value = expense;
            form.amount = parseFloat(expense.pivot?.amount || 0);
            form.payment_method = '';
            form.mobile_money_number = '';
            form.bank_reference = '';
            form.payment_date = new Date().toISOString().split('T')[0];
            form.notes = '';
            amountError.value = '';

            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            modal.show();
        };

        const viewPaymentHistory = async (expense) => {
            selectedExpense.value = expense;
            paymentHistoryLoading.value = true;

            try {
                const response = await axios.get(`/api/students/${student.value.id}/expenses/${expense.id}/payments`);
                paymentHistory.value = response.data;

                const modal = new bootstrap.Modal(document.getElementById('historyModal'));
                modal.show();
            } catch (error) {
                console.error('Failed to fetch payment history:', error);
                showAlert('Error', 'Failed to load payment history', 'error');
            } finally {
                paymentHistoryLoading.value = false;
            }
        };

        const closeHistoryModal = () => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('historyModal'));
            if (modal) modal.hide();
        };

        const submitPayment = async () => {
            if (isSubmitting.value || amountError.value) return;

            isSubmitting.value = true;
            NProgress.start();

            try {
                const studentId = student.value.id;
                const expenseId = selectedExpense.value.id;

                const payload = {
                    ...form,
                    amount: parseFloat(form.amount)
                };

                await axios.post(`/api/studentExpensePayment/${studentId}/${expenseId}`, payload);

                showAlert('Success', 'Payment recorded successfully', 'success');
                await refreshData();
                cancel();
            } catch (error) {
                console.error('Payment error:', error);
                let errorMessage = error.response?.data?.message || 'Failed to record payment';

                if (error.response?.status === 422) {
                    errorMessage = Object.values(error.response.data.errors).join('<br>');
                }

                showAlert('Error', errorMessage, 'error');
            } finally {
                isSubmitting.value = false;
                NProgress.done();
            }
        };

        const cancel = () => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
            if (modal) modal.hide();
        };

        const showAlert = (title, text, icon, toast = false) => {
            const options = {
                title,
                text,
                icon,
                toast: toast,
                position: toast ? 'top-end' : 'center',
                showConfirmButton: !toast,
                timer: toast ? 3000 : undefined,
                timerProgressBar: toast,
            };

            Swal.fire(options);
        };

        // Lifecycle hooks
        onMounted(() => {
            getExpenseTypes();

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        return {
            student,
            loading,
            showPaymentForm,
            selectedExpense,
            isSubmitting,
            form,
            amountError,
            paymentHistory,
            paymentHistoryLoading,
            filter,
            filteredExpenses,
            pendingExpensesCount,
            paidExpensesCount,
            totalAmount,
            totalPaidForExpense,
            formatCurrency,
            formatDate,
            formatDateTime,
            isExpenseOverdue,
            isPaymentAllowed,
            getPaymentTooltip,
            validateAmount,
            getExpenseTypes,
            getExpenseTypeName,
            getExpenseOptionName,
            refreshData,
            loadPaymentForm,
            viewPaymentHistory,
            closeHistoryModal,
            submitPayment,
            cancel,
            showAlert
        };
    }
});

// Add tooltip directive
app.directive('tooltip', {
    mounted(el, binding) {
        new bootstrap.Tooltip(el, {
            title: binding.value,
            placement: binding.arg || 'top',
            trigger: 'hover'
        });
    }
});

app.mount('#studentExpenseList');
</script>
@endsection