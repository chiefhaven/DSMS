@extends('layouts.backend')

@section('content')
<!-- Hero -->
<div id="studentExpenseList" class="bg-body-light">
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
            </div>
            <div class="block-content">
                <div v-if="student && student.expenses.length > 0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Group</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>List status</th>
                                    <th>Payment status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(expense, index) in student.expenses" :key="expense.id">
                                    <td>@{{ index + 1 }}</td>
                                    <td>@{{ expense.group }}</td>
                                    <td>@{{ expense.pivot?.expense_type || 'N/A' }}</td>
                                    <td>K@{{ formatCurrency(expense.amount) }}</td>
                                    <td>
                                        <span v-if="expense.approved" class="badge bg-success">Approved</span>
                                        <span v-else class="badge bg-warning">Pending</span>
                                    </td>
                                    <td>
                                        <span v-if="expense.pivot?.status === 'paid'" class="badge bg-success">Paid</span>
                                        <span v-else class="badge bg-warning">Pending</span>
                                    </td>
                                    <td>
                                        <button v-if="!expense.approved" @click="loadPaymentForm(expense)" class="btn btn-primary rounded-pill px-4">
                                            Pay
                                        </button>
                                        <span v-else class="text-muted">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-else class="alert alert-info">
                    No expenses found for this student.
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="block-title text-white fs-4 fw-bold" id="paymentModalLabel">Make payment</h5>
                    <button type="button" class="btn-close" @click="cancel" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="submitPayment">
                    <div class="mb-3">
                        <label class="form-label">Expense Group</label>
                        <input type="text" class="form-control" :value="selectedExpense.group" disabled />
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Expense Type</label>
                        <input type="text" class="form-control" :value="selectedExpense.pivot?.expense_type || 'N/A'" disabled />
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount (MWK)</label>
                        <input
                        type="number"
                        step="0.01"
                        id="amount"
                        class="form-control"
                        v-model.number="form.amount"
                        required
                        />
                    </div>

                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select id="payment_method" class="form-control" v-model="form.payment_method" required>
                        <option value="">Select</option>
                        <option value="Cash">Cash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Mobile Money">Mobile Money</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary me-2 rounded-pill px-4">Submit</button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" @click="cancel">Cancel</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
        </div>

    </div>
</div>

<script setup>
const { createApp, ref, reactive, onMounted } = Vue

const app = createApp({
    setup() {
        const student = ref(@json($student))
        const showPaymentForm = ref(false)
        const selectedExpense = ref({})
        const form = reactive({
            amount: 0,
            payment_method: '',
        })

        const formatCurrency = (value) => {
            return Number(value).toLocaleString('en-MW', { minimumFractionDigits: 2 })
        }


        const submitPayment = async () => {
            try {
                const studentId = student.value.id
                const expenseId = selectedExpense.value.id

                await axios.post(`/api/studentExpensePayment/${studentId}/${expenseId}`, form)

                showAlert('Payment successful.', error.response.data.message, {
                    icon: 'success',
                    toast: true,
                })
                window.location.reload()
            } catch (error) {
                console.error('Payment error:', error)
                showAlert('Failed to make payment.', error.response.data.message, {
                    icon: 'error',
                    toast: false,
                    confirmText: 'OK',
                    showCancel: false
                })
            }
        }

        const loadPaymentForm = (expense) => {
            selectedExpense.value = expense
            form.amount = parseFloat(expense.amount)
            form.payment_method = ''

            // Show modal
            const modalEl = document.getElementById('paymentModal')
            const modal = new bootstrap.Modal(modalEl)
            modal.show()
        }

        const cancel = () => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'))
            if (modal) modal.hide()
        }

        const showAlert = (
                message = '', // title
                detail = '',  // text
                {
                    icon = 'info',
                    toast = true,
                    confirmText = 'OK',
                    showCancel = false,
                    cancelText = 'Cancel'
                } = {}
            ) => {
                const baseOptions = {
                    icon,
                    title: message,
                    text: detail,
                    toast,
                    position: toast ? 'top-end' : 'center',
                    showConfirmButton: !toast,
                    confirmButtonText: confirmText,
                    showCancelButton: showCancel,
                    cancelButtonText: cancelText,
                    timer: toast ? 3000 : undefined,
                    timerProgressBar: toast,
                    didOpen: (toastEl) => {
                        if (toast) {
                            toastEl.addEventListener('mouseenter', Swal.stopTimer);
                            toastEl.addEventListener('mouseleave', Swal.resumeTimer);
                        }
                    }
                };

                return Swal.fire(baseOptions);
            };

        return {
            student,
            formatCurrency,
            form,
            showPaymentForm,
            selectedExpense,
            loadPaymentForm,
            submitPayment,
            cancel,
        }
    }
})

app.mount('#studentExpenseList')
</script>
@endsection
