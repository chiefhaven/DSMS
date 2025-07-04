@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Add expense</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">

            @if(Session::has('message'))
            <div class="alert alert-info">
              {{Session::get('message')}}
            </div>
          @endif
        </nav>
      </div>
    </div>
  </div>

<div class="content content-full" id="expense">
<div class="row">
    <div class="col-md-5 block-rounded block-bordered">
        <div class="block block-rounded block-themed block-transparent mb-0" style="background-color:#ffffff">
            <div class="block-content">
                <form class="mb-5" action="{{ url('/add-expense') }}" method="post" enctype="multipart/form-data" onsubmit="return true;">
                    @csrf
                    <div class="col-12 form-floating mb-4">
                        <input type="text" class="form-control" id="expense_group_name" name="expense_group_name" v-model="state.expenseGroupName" placeholder="DD MM YY" required>
                        <label for="invoice_discount">Booking Date</label>
                    </div>
                    <div class="col-12 form-floating mb-4">
                        <select class="form-control" id="expenseGroupType" name="expenseGroupType" v-model="state.expenseGroupType" placeholder="Select expense Type" :disabled="Object.keys(state.selectedStudents).length != 0">
                            <option v-for="option in expenseTypes" :value="option.id">
                                @{{ option.name }}
                            </option>
                        </select>
                        <label for="expenseGroupType">Group Expense Type</label>
                    </div>
                    <div class="col-12 form-floating mb-4">
                        <input type="text" class="form-control" id="expense_description" name="expense_description" v-model="state.expenseDescription" placeholder="Enter Expense Description">
                        <label for="invoice_discount">Expense notes</label>
                    </div>
                    {{--  <div class="col-12 form-floating mb-4">
                        <input type="number" class="form-control" id="amount" name="amount" v-model="state.amount" required>
                        <label for="amount">Amount per student</label>
                    </div>  --}}
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7 block block-rounded block-bordered">
        <h2 class="flex-grow-1 fs-4 fw-semibold my-2 my-sm-3">Add student to the group</h1>
            <div v-if="state">
                <div class="row haven-floating">
                    <div class="col-6 form-floating mb-4 text-uppercase">
                        <input
                            class="form-control"
                            id="student"
                            name="student"
                            v-model="state.studentName"
                            @input="studentSearch()"
                            @blur="onStudentChange($event)"
                            placeholder="Select student"
                            required>
                        <label for="student" class="text-capitalize">Select student</label>
                    </div>
                    <div class="col-6 form-floating mb-4">
                        <select class="form-control" v-if="selectedExpenseType" id="expenseTypesOption" name="expenseTypesOption" v-model="state.expenseTypesOption" placeholder="Select expense Type" required>
                            <option disabled value="">Select option</option>
                            <option
                                v-for="opt in selectedExpenseType.expense_type_options"
                                :value="opt.id"
                            >
                                @{{ opt.name }} - (@{{ opt.amount_per_student }})
                            </option>
                        </select>
                        <label for="expenseTypesOption">Expense Type</label>
                    </div>
                </div>
                <div class="block-content block-content-full text-end">
                    <button type="submit" @click="addStudentToGroup()" class="btn btn-primary rounded-pill px-4">Add to list</button>
                </div>
                <h2 class="flex-grow-1 fs-5 fw-semibold my-2 my-sm-3 border-lg mb-5">Select students</h2>
                    <hr>
                <div>
                    <table class="table table-striped">
                        <thead class="bg-primary text-white">
                          <tr>
                            <th>Student Name</th>
                            <th>Expense Type</th>
                            <th>Amount</th>
                            <th class="text-end">Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(student, index) in state.selectedStudents" :key="index">
                            <td>
                              @{{ student.studentName }}
                              <div
                                v-if="student.expenses && student.expenses.some(e => e.pivot?.repeat === 1)"
                                class="text-danger fw-bold small mt-1">
                                Repeating
                              </div>
                            </td>
                            <td>@{{ student.expenseTypesOptionName }}</td>
                            <td>@{{ student.expenseTypesOptionAmount }}</td>
                            <td class="text-end">
                              <button class="btn btn-danger btn-sm" @click="removeStudentFromGroup(index)">
                                Remove
                              </button>
                            </td>
                          </tr>
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
    <div class="block-content block-content-full text-end">
        <button type="submit" :disabled="state.isSubmitButtonDisabled" @click="saveExpense()" class="btn btn-primary rounded-pill px-4">
            <template v-if="state.isLoading">
                Processing...
              </template>
              <template v-else>
                @{{ state.buttonText }}
              </template>
        </button>
    </div>
</div>
</div>
<!-- END Hero -->

    <script setup>

        const addExpense = createApp({
        setup() {
            const currentDate = new Date();
            const options = { day: 'numeric', month: 'long', year: 'numeric'};
            const state = ref({
                amount: 0,                 // Represents the amount an expense
                expenseGroupName: currentDate.toLocaleDateString(options),       // Name of the expense group or category
                expenseDescription: '',       // Name of the expense group or category
                studentName: '',
                studentId:'',
                expenseTypesOption: '',            // Type of expense
                selectedStudents: [],       // Array of selected students (possibly for group payments or expenses)
                paymentMethod: 'Cash', // Preferred payment method (defaulting to 'Airtel Money')
                errors: [],              // Array to store any validation or error messages
                isSubmitButtonDisabled: false,
                isLoading: false,
                buttonText: 'Submit',
                expenseGroupType: '',
            })

            const expenseTypes = ref([])

            // Options for payment methods
            const paymentMethodOptions = ref([
                { text: 'Cash', value: 'Cash' },
                { text: 'Bank', value: 'Bank' },
                { text: 'AirtelMoney', value: 'AirtelMoney' }
            ])

            var hasError = ref(false)

            function addStudentToGroup() {
                hasError.value = false;

                if (!state.value.studentName) {
                    notification('Student name must be filled', 'error');
                    hasError.value = true;
                    return hasError;
                }

                if (!state.value.expenseTypesOption) {
                    notification('Expense Type must be filled', 'error');
                    hasError.value = true;
                    return hasError;
                }

                const alreadyInList = state.value.selectedStudents.some(
                    item => item.studentId === state.value.studentId
                );

                if (alreadyInList) {
                    notification('Student already in list', 'error');
                    hasError.value = true;
                    return hasError;
                }

                axios.post('/checkStudent', {
                    student: state.value.studentId,
                    expenseTypesOption: state.value.expenseTypesOption
                }).then(response => {
                    const { feedback, message } = response.data;

                    if (feedback === "success") {
                        const alreadyExists = state.value.selectedStudents.some(
                            s =>
                                s.studentId === state.value.studentId &&
                                s.expenseTypesOption === state.value.expenseTypesOption
                        );

                        if (!alreadyExists) {
                            const optionId = state.value.expenseTypesOption;
                            const optionName = selectedExpenseType.value?.expense_type_options.find(opt => opt.id === optionId)?.name || 'Retry adding the student...';
                            const optionAmount = selectedExpenseType.value?.expense_type_options.find(opt => opt.id === optionId)?.amount_per_student || 0;

                            state.value.selectedStudents.push({
                                studentId: state.value.studentId,
                                studentName: state.value.studentName,
                                expenseTypesOption: optionId,
                                expenseTypesOptionName: optionName,
                                expenseTypesOptionAmount: optionAmount,
                                expenses: [
                                {
                                    pivot: { repeat: 0 }
                                }
                                ]
                            });

                            state.value.studentName = '';
                            notification(message, 'success');
                        } else {
                            showAlert('Student already selected.', '', {
                                toast: true,
                                icon: 'warning',
                                confirmText: 'Ok'
                            });
                        }

                    } else if (feedback === "alreadyExists") {
                        Swal.fire({
                            title: 'Student repeating?',
                            text: message,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Continue',
                            cancelButtonText: 'Cancel',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                state.value.selectedStudents.push({
                                    studentId: state.value.studentId,
                                    studentName: state.value.studentName,
                                    expenseTypesOption: state.value.expenseTypesOption,
                                    expenses: [
                                        {
                                            pivot: {
                                                repeat: 1
                                            }
                                        }
                                    ]
                                });

                                state.value.studentName = '';
                                notification('Student added despite repeat', 'info');
                            }
                        });

                    } else {
                        showAlert('Student can not be selected', message, {
                            toast: false,
                            icon: 'warning',
                            confirmText: 'Ok'
                        });
                    }

                }).catch(error => {
                    console.error(error);
                    showAlert('Error', 'Something went wrong. Please try again.', {
                        toast: false,
                        icon: 'error',
                        confirmText: 'Close'
                    });
                });
            }

            function removeStudentFromGroup(index) {

                state.value.selectedStudents.splice(index, 1)
            }

            const selectedExpenseType = computed(() => {
                return expenseTypes.value.find(type => type.id === state.value.expenseGroupType);
            });

            const saveExpense = async()=> {
                if(Object.keys( state.value.selectedStudents ).length == 0){
                    showAlert('Can not save', 'Student list must not be empty; add students or cancel the creation.', {
                        toast: false,
                        icon: 'error',
                        confirmText: 'Ok'
                    });

                    return false
                }


                if( !state.value.expenseGroupName){
                    notification('Expense Group Name, must be filled', 'error')
                    return false
                }

                try{
                    NProgress.start();
                    state.value.isSubmitButtonDisabled = true;
                    state.value.isLoading = true;

                    console.log('Expense data to be saved:', state.value.selectedStudents)

                    const response = await axios.post('/storeexpense', {
                        students: state.value.selectedStudents,
                        expenseGroupName: state.value.expenseGroupName,
                        expenseDescription: state.value.expenseDescription,
                        expenseGroupType: state.value.expenseGroupType,
                    });

                    notification('Expense added successfully, page redirecting...', 'success');
                    window.location.replace('/expenses');
                }catch (error) {
                    notification('An error occurred while saving the expense. Please try again.', 'error');
                    state.value.isSubmitButtonDisabled = false;
                    state.value.isLoading = false;
                    state.value.buttonText = 'Submit';
                    console.log(error);
                    if (error.response && error.response.data) {
                        console.error('Error response:', error.response.data);
                        showAlert('Error', error.response.data.message || 'An unexpected error occurred.', {
                            toast: false,
                            icon: 'error',
                            confirmText: 'Close'
                        });
                    } else {
                        console.error('Error:', error);
                        showAlert('Error', 'An unexpected error occurred. Please try again later.', {
                            toast: false,
                            icon: 'error',
                            confirmText: 'Close'
                        });
                    }
                } finally {
                    NProgress.done();
                }
            }

            const onStudentChange = async(event) => {
                state.value.studentName = event.target.value;
            }

            function studentSearch() {
                var path = "{{ route('expense-student-search') }}";

                $('#student').typeahead({
                    minLength: 2,
                    autoSelect: true,
                    highlight: true,
                    source: function (query, process) {
                        $.get(path, { student: query })
                            .done(function (data) {
                                if (data.length === 0) {
                                    notification('Student not found or not enrolled, search another name', 'error');
                                    return process([]);
                                }
                                return process(data);
                            })
                            .fail(function () {
                                notification('Error fetching student data', 'error');
                                return process([]);
                            });
                    },
                    updater: function (item) {
                        if (item && item.id) {
                            state.value.studentId = item.id;
                        } else {
                            notification('Invalid student selected', 'error');
                        }

                        return item.name || '';
                    }
                });
            }

            const notification = ($text, $icon) =>{
                Swal.fire({
                    toast: true,
                    position: "top-end",
                    html: $text,
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

            onMounted(() => {
                getExpenseTypes();

                $('#expense_group_name').datepicker({
                    format: 'dd/mm/yyyy',
                    autoclose: true,
                    todayHighlight: true
                  }).on('changeDate', function() {
                    state.value.expenseGroupName = $(this).val();
                  });

                  const today = new Date();
                  $('#expense_group_name').datepicker('setDate', today);

                  const day = String(today.getDate()).padStart(2, '0');
                  const month = String(today.getMonth() + 1).padStart(2, '0');
                  const year = today.getFullYear();
                  state.value.expenseGroupName = `${day}/${month}/${year}`;
            });

            const getExpenseTypes = () => {
                axios.get('/api/fetch-expense-types')
                    .then(response => {
                        expenseTypes.value = response.data;
                    })
                    .catch(error => {
                        console.error('Error fetching expense types:', error);
                });
            };

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
                addStudentToGroup,
                removeStudentFromGroup,
                saveExpense,
                studentSearch,
                onStudentChange,
                state,
                hasError,
                paymentMethodOptions,
                expenseTypes,
                selectedExpenseType,
            }
        }
        })
        addExpense.mount('#expense')
    </script>
@endsection

