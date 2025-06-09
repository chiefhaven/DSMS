@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Review expense</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
            <a href="/expenses" class="btn btn-primary">All expenses</a>
        </nav>
      </div>
    </div>
  </div>
<div class="content content-full" id="expense">
    @if(Session::has('message'))
        <div class="alert alert-info">
            {{Session::get('message')}}
        </div>
    @endif
<div class="row">
    <div class="row">
        <!-- Expense Summary Card -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Expense Details</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Booking Date:</span>
                            <span>@{{ formatDate(state.expenseGroupName) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Type:</span>
                            <span class="badge bg-info">@{{ state.expenseGroupType }}</span>
                        </li>
                        <li class="list-group-item">
                            <span class="fw-bold">Description:</span>
                            <p class="mb-0">@{{ state.expenseDescription }}</p>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Amount/Student:</span>
                            <span class="text-success">@{{ formatter.format(state.amount) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Total Students:</span>
                            <span class="badge bg-primary rounded-pill">{{ $expense->Students->count() }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Requested By:</span>
                            <span>{{ $expense->administrator->fname }} {{ $expense->administrator->sname }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Students List -->
        <div class="col-md-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">Students List</h5>
                </div>

                <div v-if="state.loadingData" class="card-body d-flex flex-column justify-content-center align-items-center" style="min-height: 300px;">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3 mb-0">Loading student data...</p>
                </div>

                <div v-else class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>Student</th>
                                    <th>Balance</th>
                                    <th class="text-center">Class</th>
                                    <th>Expense Type</th>
                                    <th width="15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(student, index) in state.selectedStudents" :key="student.id">
                                    <td>@{{ index + 1 }}</td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <strong>@{{ student.sname }}</strong>
                                            <small class="text-muted">@{{ student.fname }} @{{ student.mname }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span v-if="student.invoice" :class="{'text-danger': student.invoice.invoice_balance > 0, 'text-success': student.invoice.invoice_balance <= 0}">
                                            @{{ formatter.format(student.invoice.invoice_balance) }}
                                        </span>
                                        <span v-else class="badge bg-secondary">Not enrolled</span>
                                    </td>
                                    <td class="text-center">
                                        <span v-if="student.course" class="badge bg-primary">
                                            @{{ student.course.class }}
                                        </span>
                                        <span v-else class="badge bg-secondary">N/A</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            @{{ student.expenses[0]?.pivot?.expense_type || 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button
                                            :disabled="state.expenseStatus !== 0"
                                            class="btn btn-sm btn-outline-danger"
                                            @click="removeStudentFromList(student.id, index)"
                                            :title="state.expenseStatus !== 0 ? 'Editing disabled for approved expenses' : 'Remove student'"
                                        >
                                            <i class="fas fa-trash-alt me-2"></i> Remove
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Section -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-footer bg-white text-end py-3">
                    <template v-if="state.expenseStatus === 0">
                        <span class="text-warning me-3"><i class="fas fa-exclamation-circle"></i> List not approved</span>
                        <button
                            type="button"
                            @click="approveList"
                            :disabled="state.processing"
                            class="btn btn-success"
                        >
                            <span v-if="state.processing">
                                <i class="fas fa-spinner fa-spin me-1"></i> Processing...
                            </span>
                            <span v-else>
                                <i class="fas fa-check-circle me-1"></i> Approve
                            </span>
                        </button>
                    </template>
                    <template v-else>
                        <span class="text-success me-3"><i class="fas fa-check-circle"></i> List approved</span>
                        <button
                            type="button"
                            @click="approveList"
                            :disabled="state.processing"
                            class="btn btn-danger"
                        >
                            <span v-if="state.processing">
                                <i class="fas fa-spinner fa-spin me-1"></i> Processing...
                            </span>
                            <span v-else>
                                <i class="fas fa-times-circle me-1"></i> Unapprove
                            </span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<!-- END Hero -->
    <script setup>
        const { createApp, ref, reactive, onMounted } = Vue

        const formatter = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'MMK',
        });


        const app = createApp({
        setup() {
            const state = ref({
                amount: {{ $expense->amount }},                 // Represents the amount an expense
                expenseGroupName: '{{ $expense->group }}',       // Name of the expense group or category
                expenseGroupType: '',
                expenseDescription: '{{ $expense->description }}',       // Name of the expense group or category
                studentName: '', // Name of the student'
                expenseType: '',            // Type of expense
                selectedStudents: [],       // Array of selected students (possibly for group payments or expenses)
                paymentMethod: 'Cash',
                expenseId: '{{ $expense->id }}',
                totalAmount: 00,
                expenseStatus: {{ $expense->approved }},
                errors: [],                  // Array to store any validation or error messages
                loadingData: false,
                processing: false,
            })

            onMounted(async () => {
                try {
                    NProgress.start();
                    state.value.loadingData = true;
                    const res = await axios.get(`/reviewExpenseData/{{ $expense->id }}`);
                    state.value.selectedStudents = res.data;
                    totalAmount();
                } catch (error) {
                    console.error('Failed to load review expense data:', error);
                    notification('Failed to load expense data', 'error');
                } finally {
                    NProgress.done();
                    state.value.loadingData = false;
                }
            });

            const formatDate = (dateString) => {
                return dayjs(dateString, ['D/M/YYYY', 'DD/MM/YYYY', 'YYYY-MM-DD']).format('DD MMM, YYYY');
            };

            const paymentMethodOptions = ref([
                { text: 'Cash', value: 'Cash' },
                { text: 'Bank', value: 'Bank' },
                { text: 'AirtelMoney', value: 'AirtelMoney' }
            ])

            function removeStudentFromGroup(index) {
                state.value.selectedStudents.splice(index, 1)
            }

            function totalAmount(){
                state.value.totalAmount = Object.keys( state.value.selectedStudents ).length*state.value.amount
            }

            const approveList = async () => {
                try {
                    NProgress.start();
                    state.value.processing = true;

                    const response = await axios.post('/approveList', {
                        expenseId: state.value.expenseId,
                        approvedAmount: state.value.totalAmount
                    });

                    if (response.status === 200) {
                        notification('List updated successfully', 'success');
                        state.value.expenseStatus = response.data.approved === true ? 1 : 0;
                    }

                } catch (error) {
                    if (error.response && error.response.data && error.response.data.errors) {
                        const firstError = Object.values(error.response.data.errors)[0];
                        notification(firstError, 'error');
                    } else {
                        notification('Something went wrong', 'error');
                    }
                } finally {
                    NProgress.done();
                    state.value.processing = false;
                }
            };

            const removeStudentFromList = async (studentId, index) => {

                if (state.value.selectedStudents.length <= 1) {
                    showAlert('List can not be empty', 'You must have at least one student in the group.', {
                        toast: false,
                        icon: 'error',
                        confirmText: 'Ok'
                    });
                    return
                }

                try {
                    NProgress.start();

                    const response = await axios.post('/removeStudent', {
                        student: studentId,
                        expenseId: state.value.expenseId
                    });

                    if (response.status === 200) {
                        removeStudentFromGroup(index);
                        totalAmount();
                        showAlert('', 'Student removed successfully', { icon: 'success' });
                    }

                } catch (error) {
                    if (error.response && error.response.data && error.response.data.errors) {
                        const firstError = Object.values(error.response.data.errors)[0];
                        notification(firstError, 'error');
                    } else {
                        notification('Something went wrong', 'error');
                    }
                } finally {
                    NProgress.done();
                }
            };


            const saveExpense = async () => {
                if (Object.keys(state.value.selectedStudents).length === 0) {
                    notification('Student list must not be empty', 'error');
                    return false;
                }

                if (!state.value.expenseGroupName || !state.value.paymentMethod || state.value.totalAmount <= 0) {
                    notification('Expense Group Name, Payment Method, and Amount must be filled and Amount must be greater than 0', 'error');
                    return false;
                }

                try {
                    NProgress.start();

                    const response = await axios.post('/updateExpense', {
                        students: state.value.selectedStudents,
                        expenseGroupName: state.value.expenseGroupName,
                        paymentMethod: state.value.paymentMethod,
                        totalAmount: state.value.totalAmount
                    });

                    if (response.status === 200) {
                        notification('Expense saved successfully', 'success');
                        window.location.replace('/expenses');
                    }
                } catch (error) {
                    if (error.response && error.response.data && error.response.data.errors) {
                        const firstError = Object.values(error.response.data.errors)[0];
                        notification(firstError, 'error');
                    } else {
                        notification('Something went wrong', 'error');
                    }
                } finally {
                    NProgress.done();
                }
            };

            function onStudentChange(event){
                state.value.studentName = event.target.value;
            }

            function studentSearch() {
                var path = "{{ route('expense-student-search') }}";
                $('#student').typeahead({
                    source:  function (query, process) {
                    return $.get(path, { query: query }, function (data) {
                            return process(data);
                        });
                    }
                });
            }

            function notification($text, $icon){
                Swal.fire({
                    toast: true,
                    position: "top-end",
                    text: $text,
                    showConfirmButton: false,
                    timer: 5500,
                    timerProgressBar: true,
                    icon: $icon,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });
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
                removeStudentFromGroup,
                saveExpense,
                studentSearch,
                onStudentChange,
                state,
                paymentMethodOptions,
                removeStudentFromList,
                approveList,
                formatter,
                formatDate
            }
        }
        })
        app.mount('#expense')
    </script>
    <script type="text/javascript">
        $('.delete-confirm').on('click', function (e) {
            e.preventDefault();
            var form = $(this).parents('form');
            Swal.fire({
                title: 'Remove student',
                text: 'Do you want to remove student from this expense?',
                icon: 'error',
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.isConfirmed)
                    form.submit();
            });
        });

    </script>
@endsection

