@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
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
                        <input type="date" timezone="Africa/Blantyre" class="form-control" id="expense_group_name" name="expense_group_name" v-model="state.expenseGroupName" placeholder="Enter Expense Group">
                        <label for="invoice_discount">Booking Date</label>
                    </div>
                    <div class="col-12 form-floating mb-4">
                        <select class="form-control" id="expenseType" @blur="groupExpenseTypeChange($event)" name="expenseType" v-model="state.expenseGroupType" placeholder="Select expense Type" :disabled="Object.keys(state.selectedStudents).length != 0">
                            <option v-for="option in groupExpenseTypeOptions" :value="option.value">
                                @{{ option.text }}
                            </option>
                        </select>
                        <label for="expenseType">Group Expense Type</label>
                    </div>
                    <div class="col-12 form-floating mb-4">
                        <input type="text" class="form-control" id="expense_description" name="expense_description" v-model="state.expenseDescription" placeholder="Enter Expense Description">
                        <label for="invoice_discount">Expense notes</label>
                    </div>
                    <div class="col-12 form-floating mb-4">
                        <input type="number" class="form-control" id="amount" @input="totalAmount()" name="amount" v-model="state.amount">
                        <label for="amount">Amount per student</label>
                    </div>
                    <div class="col-12 form-floating mb-4">
                        Total Amount: @{{ formatter.format(state.totalAmount) }}
                    </div>
            </form>
            </div>
        </div>
    </div>
    <div class="col-md-7 block block-rounded block-bordered">
        <h2 class="flex-grow-1 fs-4 fw-semibold my-2 my-sm-3">Add student to the group</h1>
        <div v-if="state">
            <div class="row haven-floating">
                <div class="col-6 form-floating mb-4 text-uppercase">
                    <input class="form-control" id="student" name="student" :rules="isRequired" v-model="state.studentName" @input="studentSearch()" @blur="onStudentChange($event)" placeholder="Select student" required>
                    <label for="student" class="text-capitalize">Select student</label>
                </div>
                <div class="col-6 form-floating mb-4">
                    <select class="form-control" v-if="state.expenseGroupType === 'TRN'" id="expenseType" name="expenseType" v-model="state.expenseType" placeholder="Select expense Type" required>
                        <option>TRN</option>
                    </select>
                    <select class="form-control" v-else-if="state.expenseGroupType === 'Road Test'" id="expenseType" name="expenseType" v-model="state.expenseType" placeholder="Select expense Type" required>
                        <option selected>
                            Road Test
                        </option>
                    </select>
                    <select class="form-control" v-else id="expenseType" name="expenseType" v-model="state.expenseType" placeholder="Select expense Type" required>
                        <option>Highway Code I</option>
                        <option>Highway Code II</option>
                    </select>
                    <label for="expenseType">Expense Type</label>
                </div>
            </div>
            <div class="block-content block-content-full text-end">
                <button type="submit" @click="addStudentToGroup()" class="btn btn-primary">Add to list</button>
            </div>
            <h2 class="flex-grow-1 fs-5 fw-semibold my-2 my-sm-3 border-lg mb-5">Select students</h2>
                <hr>
            <div>
                <div v-for="(student, index) in state.selectedStudents" :key="student.index">
                    <div class="row mb-2">
                        <div class="col-sm-6 text-uppercase">@{{ student.fname }} @{{ student.mname }} @{{ student.sname }}</div>
                        <div class="col-sm-4">
                            <div v-if="student.expenses && student.expenses.length">
                                <div v-for="expense in student.expenses" :key="expense.id">
                                  <!-- Assuming pivot is part of each expense object -->
                                  <div v-if="expense.pivot">
                                    @{{ expense.pivot.expense_type }}
                                  </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-2 text-end"><span><button class="btn btn-danger btn-sm" @click="removeStudentFromList(student.id, index)">Remove</button></span></div>
                    </div>
                    <hr>
                </div>
            </div>
        </div>
    </div>
    <div class="block-content block-content-full text-end">
        <button type="submit" @click="updateExpense()" class="btn btn-primary">Update</button>
    </div>
</div>
</div>
<!-- END Hero -->
<script setup>
    const { createApp, ref, reactive, onMounted } = Vue
    const { defineRule, configure, useForm, useField, ErrorMessage } = VeeValidate

    function isRequired(value) {
        if (value && value.trim()) {
          return true;
        }
        return 'This is required';
      }

    const app = createApp({
      setup() {
        const currentDate = new Date();
        const options = { day: 'numeric', month: 'long', year: 'numeric'};
        const state = ref({
            amount: {{ $expense->amount }},
            totalAmount:0,
            expenseGroupName: '{{ $expense->group }}',       // Name of the expense group or category
            expenseGroupType: '{{ $expense->group_type }}',
            expenseDescription: '{{ $expense->description }}',       // Name of the expense group or category
            studentName: '', // Name of the student'
            fname: '', // Name of the student'
            sname: '', // Name of the student'
            mname: '', // Name of the student'
            expenseId: '{{ $expense->id }}',
            expenseType: '',            // Type of expense
            selectedStudents: [],       // Array of selected students (possibly for group payments or expenses)
            errors: []                  // Array to store any validation or error messages
        })

        onMounted(async () => {
            const res = await axios.get("/reviewExpenseData/{{ $expense->id }}")
            state.value.selectedStudents = res.data
            totalAmount()
          })

        const formatter = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'MMK',
        });

        const groupExpenseTypeOptions = ref([
            { text: 'TRN', value: 'TRN' },
            { text: 'Theory', value: 'Theory' },
            { text: 'Road Test', value: 'Road Test' }
        ])

        function groupExpenseTypeChange(event){
            if(event.target.selectedOptions[0].value === 'Theory'){
                state.value.expenseType = 'Choose Highway Code...'
            }

            state.value.expenseType = event.target.selectedOptions[0].value
        }

        var hasError = ref(false)

        function addStudentToGroup() {
            if (!state.value.studentName) {
                notification('Student name must be filled', 'error')
                hasError.value = true
                return hasError
            }

            if (!state.value.expenseType) {
                notification('Expense Type must be filled', 'error')
                hasError.value = true
                return hasError
            }

            if(!state.value.selectedStudents.some(item => item.studentName === state.value.studentName)){
                    var student = state.value.studentName.split(" ")
                    console.log(state.value.selectedStudents);
                    state.value.selectedStudents.push({fname:student[0], mname:student[1], sname:student[2],
                        expenses: [{
                            pivot: {
                                expense_type: state.value.expenseType
                              }
                      }]
                    })
                    state.value.studentName =''
                    totalAmount()
                }else{
                    notification('Student already in list', 'error')
                    hasError.value = true
                    return hasError
                }
        }

        function removeStudentFromGroup(index) {
            state.value.selectedStudents.splice(index, 1)
            totalAmount()
        }

        function updateExpense(){

            if(Object.keys( state.value.selectedStudents ).length == 0){
                notification('Student list must not be empty', 'error')
                return false
            }

            if( !state.value.expenseGroupName){
                notification('Expense Group Name, Payment Method and Amount must be filled and Amount must be greater than 0', 'error')
                return false
            }

            axios.post('/updateExpense', {expenseId:state.value.expenseId, students:state.value.selectedStudents, expenseGroupName:state.value.expenseGroupName, expenseDescription:state.value.expenseDescription, expenseGroupType:state.value.expenseGroupType, expenseAmount: state.value.amount}).then(response => {
                //console.log(response.data)
                if(response.status==200){
                    console.log(response.data)
                    notification('Expense updated successfully','success')
                    //window.location.replace('/expenses')
                }
                else if(error.response.data.errors){
                    notification('error.response.data.errors.message','error')
                    //console.log(error.response.data.errors)
                }
                else{
                    return false
                }
            });

            //
        }

        function onStudentChange(event){
            state.value.studentName = event.target.value;  // Now you should have access to your selected option.
         }

        function totalAmount(){
            state.value.totalAmount = Object.keys( state.value.selectedStudents ).length*state.value.amount
        }

        function removeStudentFromList(studentId, index) {
            axios.post('/removeStudent', {student:studentId, expenseId: state.value.expenseId}).then(response => {
                if(response.status==200){
                    removeStudentFromGroup(index)
                    totalAmount()
                    notification('Student removed successfully','success')
                }
                else if(error.response.data.errors){
                    notification('error.response.data.errors.message','error')
                }
                else{
                    return false
                }
            });
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
            addStudentToGroup,
            removeStudentFromGroup,
            updateExpense,
            studentSearch,
            onStudentChange,
            state,
            hasError,
            groupExpenseTypeOptions,
            groupExpenseTypeChange,
            isRequired,
            removeStudentFromList,
            formatter,
            totalAmount

        }
      }
    })
    app.use(VeeValidate);
    app.mount('#expense')
</script>
<script type="text/javascript">

</script>
@endsection
