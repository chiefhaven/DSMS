@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Edit expense</h1>
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
        <div class="col-md-5 block block-rounded block-bordered">
            <div class="block-themed block-transparent mb-0">
                <div class="block-content">
                    <form class="mb-5" action="{{ url('/add-expense') }}" method="post" enctype="multipart/form-data" onsubmit="return true;">
                            @csrf
                            <div class="col-12 form-floating mb-4">
                                <input type="text" class="form-control" id="expense_group_name" name="expense_group_name" v-model="state.expenseGroupName" placeholder="Enter Expense Group" />
                                <label for="invoice_discount">Booking Date</label>
                            </div>
                            <div class="col-12 form-floating mb-4">
                                <select class="form-control" id="expenseType" @blur="groupExpenseTypeChange($event)" name="expenseType" v-model="state.expenseGroupType" placeholder="Select expense Type" :disabled="Object.keys(state.selectedStudents).length != 0">
                                    <option v-for="option in groupExpenseTypeOptions" :value="option.value">
                                        @{{ option.text }}
                                    </option>
                                </select>
                                <label for="expenseType">List Expense Type</label>
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
            <div class="block-content">

                <h2 class="flex-grow-1 fs-4 fw-semibold my-2 my-sm-3">Add student to the list</h2>
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

                    <h2 class="flex-grow-1 fs-5 fw-semibold my-2 my-sm-3 border-lg mb-5">Selected students</h2>

                    <div v-if="state.loadingData" class="d-flex flex-column justify-content-center align-items-center" style="height: 300px;">
                        <span class="spinner-border text-primary"></span>
                        <p class="mt-3">Loading data...</p>
                    </div>

                    <div v-else>
                        <div v-if="state.selectedStudents.length === 0" class="alert alert-info">
                            No students selected yet.
                        </div>

                        <div v-else class="table responsive">
                            <table class="table table-striped">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th class="col-sm-6 text-uppercase">Student</th>
                                        <th class="col-sm-4">Expense Type</th>
                                        <th class="col-sm-2 text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(student, index) in state.selectedStudents" :key="student.id">
                                        <td class="text-uppercase">
                                            @{{ student.fname }} @{{ student.mname }} <strong>@{{ student.sname }}</strong>
                                        </td>
                                        <td>
                                            <div v-if="student.expenses && student.expenses.length">
                                                @{{ student.expenses.map(e => e.pivot?.expense_type).filter(Boolean).join(', ') }}
                                            </div>
                                            <div v-else class="text-muted">N/A</div>
                                        </td>
                                        <td class="text-end">
                                            <button
                                                class="btn btn-danger btn-sm"
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
        </div>
        <div class="block-content block-content-full text-end">
            <button type="submit" :disabled="state.isSubmitButtonDisabled" @click="updateExpense()" class="btn btn-primary">
                <template v-if="state.isLoading">
                    <i class="fas fa-spinner fa-spin me-1"></i> Processing...
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
    const { createApp, ref, onMounted } = Vue

    const app = createApp({
      setup() {
        const state = ref({
          amount: {{ $expense->amount }},
          totalAmount: 0,
          expenseGroupName: '{{ $expense->group }}',
          expenseGroupType: '{{ $expense->group_type }}',
          expenseDescription: '{{ $expense->description }}',
          studentName: '',
          studentId: '',
          fname: '',
          sname: '',
          mname: '',
          expenseId: '{{ $expense->id }}',
          expenseType: '',
          selectedStudents: [],
          errors: [],
          loadingData: false,
          isLoading: false,
          buttonText: 'Submit',
          isSubmitButtonDisabled: false
        })

        const formatter = new Intl.NumberFormat('en-US', {
          style: 'currency',
          currency: 'MMK',
        })

        const groupExpenseTypeOptions = ref([
          { text: 'TRN', value: 'TRN' },
          { text: 'Theory', value: 'Theory' },
          { text: 'Road Test', value: 'Road Test' }
        ])

        const hasError = ref(false)

        function isRequired(value) {
          return value && value.trim() ? true : 'This is required'
        }

        function totalAmount() {
          state.value.totalAmount = state.value.selectedStudents.length * state.value.amount
        }

        function initDatepicker() {
            // Initialize datepicker
            $("#expense_group_name").datepicker({
              format: "dd/mm/yyyy",
              autoclose: true,
              todayHighlight: true
            })

            // Set initial date from Vue state if it exists, otherwise use today's date
            const initialDate = state.value.expenseGroupName || new Date()
            $("#expense_group_name").datepicker('setDate', initialDate)

            // Update Vue state when date changes
            $("#expense_group_name").on('changeDate', function(e) {
              state.value.expenseGroupName = e.format('dd/mm/yyyy')
            })

            // Also watch for manual input changes
            $("#expense_group_name").on('change', function() {
              state.value.expenseGroupName = $(this).val()
            })
          }

        function studentSearch() {
          const path = "{{ route('expense-student-search') }}"

          $('#student').typeahead({
            minLength: 2,
            autoSelect: true,
            highlight: true,
            source: function (query, process) {
              return $.get(path, { query: query }, function (data) {
                return process(data)
              })
            },
            updater: function (item) {
              state.value.studentId = item.id
              return item
            }
          })
        }

        onMounted(async () => {
          state.value.loadingData = true
          const res = await axios.get("/reviewExpenseData/{{ $expense->id }}")
          state.value.selectedStudents = res.data
          totalAmount()
          state.value.loadingData = false
          studentSearch()
          initDatepicker()
        })

        function groupExpenseTypeChange(event) {
          const selected = event.target.selectedOptions[0].value
          state.value.expenseType = selected === 'Theory' ? 'Choose Highway Code...' : selected
        }

        function onStudentChange(event) {
          state.value.studentName = event.target.value
        }

        function expenseGroupNameChange(event) {
          state.value.expenseGroupName = event.target.value
        }

        function notification(text, icon) {
          Swal.fire({
            toast: true,
            position: "top-end",
            html: text,
            showConfirmButton: false,
            timer: 5500,
            timerProgressBar: true,
            icon,
            didOpen: (toast) => {
              toast.onmouseenter = Swal.stopTimer
              toast.onmouseleave = Swal.resumeTimer
            }
          })
        }

        function showAlert(message = '', detail = '', options = {}) {
          const {
            icon = 'info',
            toast = true,
            confirmText = 'OK',
            showCancel = false,
            cancelText = 'Cancel'
          } = options

          return Swal.fire({
            icon,
            title: message,
            text: detail,
            toast,
            position: toast ? 'top-end' : 'center',
            showConfirmButton: !toast,
            confirmButtonText: confirmText,
            showCancelButton,
            cancelButtonText,
            timer: toast ? 3000 : undefined,
            timerProgressBar: toast,
            didOpen: (toastEl) => {
              if (toast) {
                toastEl.addEventListener('mouseenter', Swal.stopTimer)
                toastEl.addEventListener('mouseleave', Swal.resumeTimer)
              }
            }
          })
        }

        function addStudentToGroup() {
          if (!state.value.studentName) {
            notification('Student name must be filled', 'error')
            return hasError.value = true
          }

          if (!state.value.expenseType) {
            notification('Expense Type must be filled', 'error')
            return hasError.value = true
          }

          const student = state.value.studentName.split(" ")
          if (state.value.selectedStudents.some(item => item.studentId === state.value.studentId)) {
            notification('Student already in list', 'error')
            return hasError.value = true
          }

          axios.post('/checkStudent', {
            student: state.value.studentId,
            expenseType: state.value.expenseType
          }).then(response => {
            if (response.data.feedback === "success") {
              state.value.selectedStudents.push({
                studentId: state.value.studentId,
                fname: student[0],
                mname: student[1],
                sname: student[2],
                expenses: [{
                  pivot: { expense_type: state.value.expenseType }
                }]
              })
              state.value.studentName = ''
              state.value.studentId = ''
              totalAmount()
              notification(response.data.message, 'success')
            } else {
              notification(response.data.message, 'error')
            }
          })
        }

        function removeStudentFromGroup(index) {
          if (state.value.selectedStudents.length <= 1) {
            showAlert('List can not be empty', 'You must have at least one student in the group.', {
              toast: false,
              icon: 'error',
              confirmText: 'Ok'
            })
            return
          }

          state.value.selectedStudents.splice(index, 1)
          totalAmount()
        }

        function removeStudentFromList(studentId, index) {
          if (state.value.selectedStudents.length <= 1) {
            showAlert('List can not be empty', 'You must have at least one student in the group.', {
              toast: false,
              icon: 'error',
              confirmText: 'Ok'
            })
            return
          }

          axios.post('/removeStudent', {
            student: studentId,
            expenseId: state.value.expenseId
          }).then(response => {
            if (response.status === 200) {
              removeStudentFromGroup(index)
              notification('Student removed successfully', 'success')
            } else {
              notification('Error removing student', 'error')
            }
          })
        }

        const updateExpense = () => {
          if (state.value.selectedStudents.length === 0) {
            notification('Student list must not be empty', 'error')
            return
          }

          if (!state.value.expenseGroupName || state.value.amount <= 0) {
            notification('Expense Group Name, Payment Method and Amount must be filled and Amount must be greater than 0', 'error')
            return
          }

          state.value.isLoading = true

          axios.post('/updateExpense', {
            expenseId: state.value.expenseId,
            students: state.value.selectedStudents,
            expenseGroupName: state.value.expenseGroupName,
            expenseDescription: state.value.expenseDescription,
            expenseGroupType: state.value.expenseGroupType,
            expenseAmount: state.value.amount
          }).then(response => {
            if (response.status === 200) {
              notification('Expense updated successfully', 'success')

              setTimeout(() => {
                window.location.replace('/expenses')
              }, 1500)

            } else {
              notification('Something went wrong...', 'error')
            }
          }).catch(() => {
            notification('Something went wrong...', 'error')
          }).finally(() => {
            state.value.isLoading = false
          })
        }

        return {
          addStudentToGroup,
          removeStudentFromGroup,
          removeStudentFromList,
          updateExpense,
          studentSearch,
          onStudentChange,
          expenseGroupNameChange,
          groupExpenseTypeChange,
          isRequired,
          formatter,
          state,
          hasError,
          groupExpenseTypeOptions,
          totalAmount
        }
      }
    })

    app.mount('#expense')
    </script>


@endsection

