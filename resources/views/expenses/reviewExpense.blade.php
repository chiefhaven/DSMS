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
    <div class="col-md-4 block block-rounded block-bordered">
        <div class="block-themed block-transparent mb-0">
            <div class="block-content pb-4">
                <div class="mb-2"><b>Booking date:</b> @{{ formatDate(state.expenseGroupName) }}</div>
                <div class="mb-2"><b>Description:</b> @{{ state.expenseDescription }}</div>
                <div class="mb-2"><b>Amount/student:</b> @{{ formatter.format(state.amount) }}</div>
                <div class="mb-2"><b>Total students:</b> {{ $expense->Students->count() }}</div>
                <div class="mb-2"><b>Requested by:</b> {{ $expense->administrator->fname }} {{ $expense->administrator->mname }} {{ $expense->administrator->sname }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-8 block block-rounded block-bordered">
        <div v-if="state">
            <div v-if="state.loadingData" class="d-flex flex-column justify-content-center align-items-center" style="height: 300px;">
                <span class="spinner-border text-primary"></span>
                <p class="mt-3">Loading data...</p>
            </div>

            <div v-else class="block-content">
                <strong>Students on the list</strong>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="bg-primary text-white">
                        <tr>
                            <th scope="col">No.</th>
                            <th scope="col">Student</th>
                            <th scope="col">Fees balance</th>
                            <th scope="col" class="text-center">Class</th>
                            <th scope="col">Expense type</th>
                            <th scope="col">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="(student, index) in state.selectedStudents" :key="student.id">
                            <td>@{{ index + 1 }}</td>
                            <td class="text-uppercase">@{{ student.fname }} @{{ student.mname }} <strong>@{{ student.sname }}</strong></td>
                            <td>
                            <span v-if="student.invoice">
                                @{{ formatter.format(student.invoice.invoice_balance) }}
                            </span>
                            <span v-else class="text-muted">Not enrolled</span>
                            </td>
                            <td class="text-center">
                            <span v-if="student.course">
                                @{{ student.course.class }}
                            </span>
                            <span v-else class="text-muted">Not enrolled</span>
                            </td>
                            <td>
                            @{{ student.expenses[0]?.pivot?.expense_type || 'N/A' }}
                            </td>
                            <td>
                                <button
                                :disabled="state.expenseStatus !== 0"
                                :title="state.expenseStatus !== 0 ? 'Editing is disabled for this expense' : 'Editing is enabled for this expense'"
                                class="btn btn-danger btn-sm delete-confirm"
                                @click="removeStudentFromList(student.id, index)"
                            >
                                Remove
                            </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
          </div>
    </div>
    <div v-if="state">
        <div v-if="state.expenseStatus === 0" class="block-content block-content-full text-end">
            List not approved
            <button type="submit"
                    @click="approveList"
                    :disabled="state.processing"
                    class="btn btn-success">
                <span v-if="state.processing">
                    <i class="fas fa-spinner fa-spin me-1"></i> Processing...
                </span>
                <span v-else>Approve</span>
            </button>
        </div>

        <div v-else class="block-content block-content-full text-end">
            List approved
            <button type="submit"
                    @click="approveList"
                    :disabled="state.processing"
                    class="btn btn-danger">
                <span v-if="state.processing">
                    <i class="fas fa-spinner fa-spin me-1"></i> Processing...
                </span>
                <span v-else>Unapprove</span>
            </button>
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

