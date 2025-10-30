@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">View expense</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn btn-primary rounded-pill px-4" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-sm-inline-block">Action</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0">
                        <div class="p-2">
                            @role(['superAdmin', 'admin'])
                                <a href="/expenses" class="dropdown-item nav-main-link">
                                    <i class="nav-main-link-icon  fas fa-file-invoice"></i>All Expenses
                                </a>
                                <a href="/editexpense/{{ $expense->id }}" class="dropdown-item nav-main-link">
                                    <i class="nav-main-link-icon  fas fa-edit"></i>Edit Expense
                                </a>
                            @endrole
                            @role(['superAdmin'])
                                <a href="/review-expense/{{ $expense->id }}" class="dropdown-item nav-main-link">
                                    <i class="nav-main-link-icon  fas fa-magnifying-glass"></i>Review Expense
                                </a>
                            @endrole
                        </div>
                    </div>
                </div>
            </ol>
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
                                    <th style="min-width: 14em">Student</th>
                                    <th style="min-width: 10em">Fees balance</th>
                                    <th class="text-center">Class</th>
                                    <th style="min-width: 10em">Expense type</th>
                                    <th class="invoice-td" >Amount</th>
                                    <th class="invoice-td" >Balance</th>
                                    <th class="invoice-td" style="min-width: 6em">Status</th>
                                    <th class="invoice-td" style="min-width: 9em">Paid by</th>
                                    <th class="invoice-td" style="min-width: 10em">Date Paid</th>
                                    <th class="invoice-td" style="min-width: 10em">Payment Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(student, index) in state.selectedStudents" :key="student.id">
                                    <td>
                                        @{{ index + 1 }}
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <a :href="`/viewstudent/${student.id}`" class="fw-bold text-decoration-none" target="_blank" rel="noopener noreferrer">
                                                <strong>@{{ student.sname }} @{{ student.fname }} @{{ student.mname }}</strong>
                                            </a>
                                            <small class="text-muted">
                                                <div
                                                    v-if="student.expenses && student.expenses.some(e => e.pivot?.repeat === 1)"
                                                    class="text-danger fw-bold small mt-1">
                                                    Repeating
                                                </div>
                                            </small>
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
                                            @{{ expenseOptionTypeName(student.expenses[0]?.pivot?.expense_type) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span>
                                            K@{{ student.expenses[0]?.pivot?.amount !== undefined ? Number(student.expenses[0].pivot.amount).toFixed(2) : 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span>
                                            K@{{ student.expenses[0]?.pivot?.balance !== undefined ? Number(student.expenses[0].pivot.balance).toFixed(2) : 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span>
                                            @{{ student.expenses[0]?.pivot?.status ? 'Paid' : 'Not Paid' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span>
                                            @{{
                                              state.admins[student.expenses[0]?.pivot?.payment_entered_by]?.administrator?.fname || '-'
                                            }}
                                          </span>
                                    </td>
                                    <td>
                                        <span>
                                            @{{ student.expenses[0]?.pivot?.paid_at ? formatDate(student.expenses[0].pivot.paid_at) : '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span>
                                            @{{ student.expenses[0]?.pivot?.payment_method ?? '-' }}
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
</div>
</div>
<!-- END Hero -->
    <script setup>

        const formatter = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'MMK',
        });


        const app = createApp({
        setup() {
            const expenseTypes = ref([]);
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
                admins: [],
            })

            onMounted(async () => {
                try {
                    NProgress.start();
                    state.value.loadingData = true;
                    const res = await axios.get(`/reviewExpenseData/{{ $expense->id }}`);
                    state.value.selectedStudents = res.data.students;
                    state.value.admins = res.data.enteredByAdmins;
                    totalAmount();

                } catch (error) {
                    console.error('Failed to load review expense data:', error);
                    notification('Failed to load expense data', 'error');
                } finally {
                    NProgress.done();
                    state.value.loadingData = false;
                }

                await getExpenseTypes();

            });

            const getExpenseTypes = async () => {
                const res = await axios.get('/api/fetch-expense-types');
                expenseTypes.value = res.data;
            };

            const expenseOptionTypeName = (id) => {
                if (!id) return '-';
                const allOptions = expenseTypes.value.flatMap(et => et.expense_type_options);
                const found = allOptions.find(opt => opt.id === id);
                return found?.name || '-';
            };

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
                formatDate,
                expenseOptionTypeName,
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

