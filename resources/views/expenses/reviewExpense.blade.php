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
    <div class="col-md-4 block-rounded block-bordered">
        <div class="block block-rounded block-themed block-transparent mb-0" style="background-color:#ffffff">
            <div class="block-content pb-4">
                <div class="mb-2"><b>Booking date:</b> @{{ state.expenseGroupName }}</div>
                <div class="mb-2"><b>Description:</b> @{{ state.expenseDescription }}</div>
                <div class="mb-2"><b>Amount/student:</b> @{{ formatter.format(state.amount) }}</div>
                <div class="mb-2"><b>Total students:</b> {{ $expense->Students->count() }}</div>
                <div class="mb-2"><b>Requested by:</b> {{ $expense->administrator->fname }} {{ $expense->administrator->mname }} {{ $expense->administrator->sname }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-8 block block-rounded block-bordered">
            <div v-if="state">
                <div>
                    <div class="row p-2 mb-4 bg-info text-white">
                        <div class="col-sm-1">No.</div>
                        <div class="col-sm-3">Student</div>
                        <div class="col-sm-2">Fees balance</div>
                        <div class="col-sm-2 text-center">Class</div>
                        <div class="col-sm-2">Expense type</div>
                        <div class="col-sm-2">Action</div>
                    </div>
                    <div v-for="(student, index) in state.selectedStudents" :key="student.index">
                        <div class="row mb-2">
                            <div class="col-sm-1 text-black">@{{ ++index }}</b></div>
                            <div class="col-sm-3 text-uppercase">@{{ student.fname }} @{{ student.mname }} <b>@{{ student.sname }}</b></div>
                            <div class="col-sm-2">@{{ formatter.format(student.invoice.invoice_balance) }}</div>
                            <div class="col-sm-2 text-center">@{{ student.course.class}}</div>
                            <div class="col-sm-2">@{{ student.expenses[0].pivot.expense_type }}</div>
                            <div class="col-sm-2">
                                <button :disabled="state.expenseStatus !== 0" class="btn btn-danger btn-sm delete-confirm" @click="removeStudentFromList(student.id, index)">Remove</button>
                            </div>
                        </div>
                        <hr>
                    </div>
                </div>
            </div>
    </div>
    <div v-if="state">
        <div v-if="state.expenseStatus === 0" class="block-content block-content-full text-end">
            List is unapproved <button type="submit" @click="approveList()" class="btn btn-success">Approve</button>
        </div>
        <div v-else class="block-content block-content-full text-end">
            List is approved <button type="submit" @click="approveList()" class="btn btn-danger">Unapprove</button>
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
                errors: []                  // Array to store any validation or error messages
            })

            onMounted(async () => {
                try {
                    NProgress.start();
                    const res = await axios.get(`/reviewExpenseData/{{ $expense->id }}`);
                    state.value.selectedStudents = res.data;
                    totalAmount();
                } catch (error) {
                    console.error('Failed to load review expense data:', error);
                    notification('Failed to load expense data', 'error');
                } finally {
                    NProgress.done();
                }
            });

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
                }
            };

            const removeStudentFromList = async (studentId, index) => {
                try {
                    NProgress.start();

                    const response = await axios.post('/removeStudent', {
                        student: studentId,
                        expenseId: state.value.expenseId
                    });

                    if (response.status === 200) {
                        removeStudentFromGroup(index);
                        totalAmount();
                        notification('Student removed successfully', 'success');
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

            return {
                removeStudentFromGroup,
                saveExpense,
                studentSearch,
                onStudentChange,
                state,
                paymentMethodOptions,
                removeStudentFromList,
                approveList,
                formatter
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

