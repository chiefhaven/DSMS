@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Approve expense</h1>
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
            <div class="block-content pb-4">
                <div class=""><b>Booking date:</b> @{{ state.expenseGroupName }}</div>
                <div class=""><b>Description:</b> @{{ state.expenseDescription }}</div>
                <div class=""><b>Amount per student:</b> MWK@{{ state.amount }}</div>
                <div class=""><b>Total requested amount:</b> MWK@{{ state.totalAmount }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-7 block block-rounded block-bordered">
            <div v-if="state">
                <h2 class="flex-grow-1 fs-5 fw-semibold my-2 my-sm-3 border-lg mb-5">Select students</h2>
                    <hr>
                <div>
                    <div v-for="(student, index) in state.selectedStudents" :key="student.index">
                        <div class="row mb-2">
                            <div class="col-sm-5 text-uppercase">@{{ student.fname }} @{{ student.mname }} <b>@{{ student.sname }}</b></div>
                            <div class="col-sm-3">MWK @{{ student.invoice.invoice_balance }}</div>
                            <div class="col-sm-2">@{{ student.course}}</div>
                            <div class="col-sm-2 text-end" v-if="state.expenseStatus === 0"><span><button class="btn btn-danger btn-sm delete-confirm" @click="removeStudentFromList(student.id, index)">Remove</button></span></div>
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
    const { defineRule, configure, useForm, useField, ErrorMessage } = VeeValidate

    function isRequired(value) {
        if (value && value.trim()) {
          return true;
        }
        return 'This is required';
      }


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
            totalAmount: '00.00',
            expenseStatus: {{ $expense->approved }},
            errors: []                  // Array to store any validation or error messages
        })

        onMounted(async () => {
            const res = await axios.get("/reviewExpenseData/{{ $expense->id }}")
            state.value.selectedStudents = res.data
            console.log(state.value.expenseStatus)
            totalAmount()
          })

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

        function approveList(){
            axios.post('/approveList', {expenseId: state.value.expenseId, approvedAmount:state.value.totalAmount}).then(response => {
                if(response.status==200){
                    notification('List updated successfully','success')
                    state.value.expenseStatus = (response.data.approved === true) ? 1 : 0

                }
                else if(error.response.data.errors){
                    notification('error.response.data.errors.message','error')
                }
                else{
                    return false
                }
            });
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

        function saveExpense(){

            if(Object.keys( state.value.selectedStudents ).length == 0){
                notification('Student list must not be empty', 'error')
                return false
            }

            if( !state.value.expenseGroupName || !state.value.paymentMethod){
                notification('Expense Group Name, Payment Method and Amount must be filled and Amount must be greater than 0', 'error')
                return false
            }

            axios.post('/updateExpense', {students:state.value.selectedStudents}).then(response => {
                if(response.status==200){
                    notification('Expense saved successfully','success')
                    window.location.replace('/expenses')
                }
                else if(error.response.data.errors){
                    notification('error.response.data.errors.message','error')
                }
                else{
                    return false
                }
            });

            //
        }

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
            isRequired,
            removeStudentFromList,
            approveList,
        }
      }
    })
    app.use(VeeValidate);
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
