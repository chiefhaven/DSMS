@extends('layouts.backend')

@section('content')
<!-- Hero -->
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
      <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Edit Expense</h1>
      <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
        @if(Session::has('message'))
          <div class="alert alert-info">{{ Session::get('message') }}</div>
        @endif
      </nav>
    </div>
  </div>
</div>
<!-- END Hero -->

<div class="content content-full" id="expense">
  <div class="row">
    <!-- Expense Form -->
    <div class="col-md-5 block block-rounded block-bordered">
      <div class="block-content">
        <form @submit.prevent="updateExpense">
          @csrf
          <div class="form-floating mb-4">
            <input type="text" class="form-control"
              v-model="state.expenseGroupName"
              placeholder="Booking Date"
              id="expense_group_name">
            <label for="expense_group_name">Booking Date</label>
          </div>

          <div class="form-floating mb-4">
            <select class="form-control"
              v-model="state.expenseGroupType"
              :disabled="state.selectedStudents.length > 0">
              <option v-for="option in expenseTypes" :value="option.id">
                @{{ option.name }}
              </option>
            </select>
            <label>List Expense Type</label>
          </div>

          <div class="form-floating mb-4">
            <input type="text" class="form-control"
              v-model="state.expenseDescription"
              placeholder="Expense Notes">
            <label>Expense Notes</label>
          </div>

          <div class="mb-4">
            <strong>Total Amount:</strong> @{{ formatter.format(state.totalAmount) }}
          </div>
        </form>
      </div>
    </div>

    <!-- Student List -->
    <div class="col-md-7 block block-rounded block-bordered">
      <div class="block-content">
        <h2 class="fs-4 fw-semibold mb-4">Add Student to the List</h2>

        <div class="row mb-4">
          <div class="col-6 form-floating mb-4">
            <input class="form-control"
              id="student"
              v-model="state.studentName"
              @input="studentSearch"
              placeholder="Select Student">
            <label for="student">Select Student</label>
          </div>

          <div class="col-6 form-floating mb-4">
            <select class="form-control"
              v-if="selectedExpenseType"
              v-model="state.expenseTypesOption">
              <option disabled value="">Select option</option>
              <option v-for="opt in selectedExpenseType.expense_type_options" :value="opt.id">
                @{{ opt.name }} - (@{{ formatter.format(opt.amount_per_student) }})
              </option>
            </select>
            <label>Expense Option</label>
          </div>
        </div>

        <div class="text-end mb-4">
          <button type="button"
            class="btn btn-primary rounded-pill px-4"
            @click="addStudentToGroup">Add to List</button>
        </div>

        <h4 class="fw-semibold">Selected Students</h4>

        <div v-if="state.loadingData" class="text-center my-5">
          <span class="spinner-border text-primary"></span>
          <p class="mt-3">Loading data...</p>
        </div>

        <div v-else>
          <div v-if="!state.selectedStudents.length" class="alert alert-info">No students selected yet.</div>

          <table v-else class="table table-striped">
            <thead class="bg-primary text-white">
              <tr>
                <th>Student</th>
                <th>Expense Option</th>
                <th>Amount</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(student, index) in state.selectedStudents" :key="student.studentId">
                <td>
                  @{{ student.fname }} @{{ student.mname }} <strong>@{{ student.sname }}</strong>
                  <div v-if="student.expenses?.[0]?.pivot?.repeat === 1" class="text-danger small">Repeating</div>
                </td>
                <td>@{{ student.expenseTypesOptionName }}</td>
                <td>@{{ formatter.format(student.expenseTypesOptionAmount) }}</td>
                <td class="text-end">
                  <button class="btn btn-danger btn-sm" @click="removeStudentFromList(student.studentId, index)">
                    Remove
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="text-end mt-4">
          <button type="button"
            class="btn btn-primary rounded-pill px-4"
            :disabled="state.isSubmitButtonDisabled"
            @click="updateExpense">
            <span v-if="state.isLoading">
              <i class="fas fa-spinner fa-spin"></i> Processing...
            </span>
            <span v-else>
              @{{ state.buttonText }}
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Vue 3 Script -->
<!-- Vue 3 Script -->
<script setup>
    const app = createApp({
    setup() {
        const state = ref({
            totalAmount: 0,
            expenseGroupType: '{{ $expense->group_type }}',
            expenseDescription: '{{ $expense->description }}',
            expenseGroupName: '{{ $expense->group }}',
            studentName: '',
            studentId: '',
            expenseId: '{{ $expense->id }}',
            expenseTypesOption: '',
            selectedStudents: [],
            isLoading: false,
            isSubmitButtonDisabled: false,
            buttonText: 'Submit',
            loadingData: false,
        });

        const expenseTypes = ref([]);
        const formatter = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'MMK' });

        const selectedExpenseType = computed(() =>
        expenseTypes.value.find(type => type.id == state.value.expenseGroupType)
        );

        const getExpenseTypes = () => {
            axios.get('/api/fetch-expense-types').then(res => {
                expenseTypes.value = res.data;
            });
        };

        const fetchExistingStudents = () => {
            state.value.loadingData = true;
            axios.get(`/reviewExpenseData/${state.value.expenseId}`)
            .then(res => {
            state.value.selectedStudents = res.data.students.map(s => {
                const pivot = s.expenses.find(e => e.pivot.expense_id === state.value.expenseId)?.pivot || {};
                const option = expenseTypes.value.flatMap(et => et.expense_type_options).find(opt => opt.id === pivot.expense_type) || {};
                return {
                studentId: s.id,
                fname: s.fname,
                mname: s.mname,
                sname: s.sname,
                expenseTypesOption: pivot.expense_type || '',
                expenseTypesOptionName: option.name || '-',
                expenseTypesOptionAmount: pivot.amount || 0,
                expenses: [{ pivot }]
                };
            });
            totalAmount();
            }).finally(() => {
                state.value.loadingData = false;
            });
        };

        const totalAmount = () => {
            state.value.totalAmount = state.value.selectedStudents.reduce(
                (sum, s) => sum + (s.expenseTypesOptionAmount || 0), 0
            );
        };

        const studentSearch = () => {
        $('#student').typeahead({
            minLength: 2,
            autoSelect: true,
            source: (query, process) =>
            $.get("{{ route('expense-student-search') }}", { query }, process),
            updater: item => {
            state.value.studentId = item.id;
            state.value.studentName = item.name;
            return item.name;
            }
        });
        };

        const addStudentToGroup = () => {
            if (!state.value.studentName || !state.value.expenseTypesOption) {
                Swal.fire('Error', 'Fill student and expense option', 'error');
                return;
            }

            const optionId = state.value.expenseTypesOption;
            const option = selectedExpenseType.value?.expense_type_options?.find(opt => opt.id == optionId) || {};
            const [fname, mname, sname] = state.value.studentName.trim().split(" ");

            if (state.value.selectedStudents.some(s => s.studentId == state.value.studentId)) {
                Swal.fire('Error', 'Student already in list', 'error');
                return;
            }

            axios.post('/checkStudent', {
                student: state.value.studentId,
                expenseTypesOption: optionId
            }).then(({ data }) => {
                const add = (repeat) => {
                state.value.selectedStudents.push({
                    studentId: state.value.studentId,
                    fname, mname, sname,
                    expenseTypesOption: optionId,
                    expenseTypesOptionName: option.name || '',
                    expenseTypesOptionAmount: 0,
                    expenses: [{ pivot: { expense_type: option.id, amount: option.amount_per_student, repeat } }]
                });
                state.value.studentName = '';
                state.value.studentId = '';
                state.value.expenseTypesOption = ''; // Clear after add
                totalAmount();
                };

                if (data.feedback === "alreadyExists") {
                Swal.fire({
                    title: 'Student repeating?',
                    text: data.message,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Continue',
                    cancelButtonText: 'Cancel'
                }).then(res => {
                    if (res.isConfirmed)
                        add(1);
                });
                } else if (data.feedback === "success") {
                    add(0);
                } else {
                Swal.fire('Error', data.message, 'error');
                }
            });
        };

        const removeStudentFromList = (studentId, index) => {
        if (state.value.selectedStudents.length <= 1) {
            Swal.fire('Error', 'List cannot be empty', 'error');
            return;
        }
        Swal.fire({
            title: 'Are you sure?',
            text: 'Remove this student?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, remove',
        }).then(result => {
            if (result.isConfirmed) {
            axios.post('/removeStudent', {
                student: studentId,
                expenseId: state.value.expenseId
            }).then(() => {
                state.value.selectedStudents.splice(index, 1);
                totalAmount();
                Swal.fire('Removed!', 'Student removed.', 'success');
            });
            }
        });
        };

        const updateExpense = () => {
        if (!state.value.selectedStudents.length) {
            Swal.fire('Error', 'List must not be empty', 'error');
            return;
        }
        state.value.isLoading = true;
        state.value.isSubmitButtonDisabled = true;

        axios.post('/updateExpense', {
            expenseId: state.value.expenseId,
            students: state.value.selectedStudents,
            expenseGroupName: state.value.expenseGroupName,
            expenseDescription: state.value.expenseDescription,
            expenseGroupType: state.value.expenseGroupType
        }).then(() => {
            Swal.fire('Success', 'Expense updated', 'success').then(() => {
            window.location.href = '/expenses';
            });
        }).catch(() => {
            Swal.fire('Error', 'Error updating expense', 'error');
        }).finally(() => {
            state.value.isLoading = false;
            state.value.isSubmitButtonDisabled = false;
        });
        };

        onMounted(() => {
        getExpenseTypes();
        fetchExistingStudents();
        studentSearch();

        $('#expense_group_name').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        }).on('changeDate', function () {
            state.value.expenseGroupName = $(this).val();
        });

        // Only set today's date if not editing existing one
        if (!state.value.expenseGroupName) {
            const today = new Date();
            $('#expense_group_name').datepicker('setDate', today);
            const day = String(today.getDate()).padStart(2, '0');
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const year = today.getFullYear();
            state.value.expenseGroupName = `${year}/${month}/${day}`;
        } else {
            $('#expense_group_name').datepicker('setDate', state.value.expenseGroupName);
        }
        });

        return {
        state,
        formatter,
        selectedExpenseType,
        expenseTypes,
        totalAmount,
        addStudentToGroup,
        removeStudentFromList,
        updateExpense,
        studentSearch
        };
    }
    });
    app.mount('#expense');
</script>
@endsection
