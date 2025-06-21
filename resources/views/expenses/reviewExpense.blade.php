@extends('layouts.backend')

@section('content')
<!-- Hero -->
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
      <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Review Expense</h1>
      <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
        <a href="/expenses" class="btn btn-primary rounded-pill px-4">All Expenses</a>
      </nav>
    </div>
  </div>
</div>

<div class="content content-full" id="expense">
  @if(Session::has('message'))
    <div class="alert alert-info">
      {{ Session::get('message') }}
    </div>
  @endif

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
              <span>@{{ state.expenseBookingDate }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span class="fw-bold">Type:</span>
              <span class="badge bg-info">@{{ expenseGroupTypeName }}</span>
            </li>
            <li class="list-group-item">
              <span class="fw-bold">Description:</span>
              <p class="mb-0">@{{ state.expenseDescription }}</p>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <span class="fw-bold">Total Amount:</span>
              <span class="text-success">@{{ formatter.format(state.totalAmount) }}</span>
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
                  <th>Amount</th>
                  <th width="15%">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(student, index) in state.selectedStudents" :key="student.studentId">
                  <td>@{{ index + 1 }}</td>
                  <td>
                    <div class="d-flex flex-column">
                      <strong>@{{ student.sname }} @{{ student.fname }} @{{ student.mname }}</strong>
                      <small v-if="student.expenses && student.expenses.some(e => e.pivot?.repeat === 1)" class="text-danger fw-bold mt-1">
                        Repeating
                      </small>
                    </div>
                  </td>
                  <td>
                    <span v-if="student.student_invoice" :class="{'text-danger': student.student_invoice.invoice_balance > 0, 'text-success': student.student_invoice.invoice_balance <= 0}">
                      @{{ formatter.format(student.student_invoice.invoice_balance) }}
                    </span>
                    <span v-else class="badge bg-secondary">Not enrolled</span>
                  </td>
                  <td class="text-center">
                    <span class="badge" :class="student.course_class !== 'N/A' ? 'bg-primary' : 'bg-secondary'">
                      @{{ student.course_class }}
                    </span>
                  </td>
                  <td>
                    <span class="badge bg-info">
                      @{{ student.expenseTypesOptionName || '-' }}
                    </span>
                  </td>
                  <td>
                    <span class="">
                      @{{ formatter.format(student.expenseTypesOptionAmount) || '-' }}
                    </span>
                  </td>
                  <td>
                    <button
                      :disabled="state.expenseStatus !== 0"
                      class="btn btn-sm btn-outline-danger"
                      @click="removeStudentFromList(student.studentId, index)"
                      :title="state.expenseStatus !== 0 ? 'Editing disabled for approved expenses' : 'Remove student'"
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

    <!-- Approval Section -->
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-footer bg-white text-end py-3">
          <template v-if="state.expenseStatus === 0">
            <span class="text-warning me-3"><i class="fas fa-exclamation-circle"></i> List not approved</span>
            <button type="button" @click="approveList" :disabled="state.processing" class="btn btn-success">
              <span v-if="state.processing"><i class="fas fa-spinner fa-spin me-1"></i> Processing...</span>
              <span v-else><i class="fas fa-check-circle me-1"></i> Approve</span>
            </button>
          </template>
          <template v-else>
            <span class="text-success me-3"><i class="fas fa-check-circle"></i> List approved</span>
            <button type="button" @click="approveList" :disabled="state.processing" class="btn btn-danger">
              <span v-if="state.processing"><i class="fas fa-spinner fa-spin me-1"></i> Processing...</span>
              <span v-else><i class="fas fa-times-circle me-1"></i> Unapprove</span>
            </button>
          </template>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Vue Script -->
<script setup>
const formatter = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'MMK' });

const app = createApp({
  setup() {
    const state = ref({
      amount: {{ $expense->amount }},
      expenseBookingDate: '{{ $expense->group }}',
      expenseGroupType: '{{ $expense->group_type }}',
      expenseDescription: '{{ $expense->description }}',
      selectedStudents: [],
      expenseId: '{{ $expense->id }}',
      expenseStatus: {{ $expense->approved }},
      loadingData: false,
      processing: false,
      totalAmount: 0
    });

    const expenseTypes = ref([]);

    // ✅ Compute the human-readable name for the group type
    const expenseGroupTypeName = computed(() =>
      expenseTypes.value.find(et => et.id === state.value.expenseGroupType)?.name || 'N/A'
    );

    const formatDate = (dateString) => dayjs(dateString).format('DD MM, YYYY');

    const getExpenseTypes = async () => {
      const res = await axios.get('/api/fetch-expense-types');
      expenseTypes.value = res.data;
    };

    const totalAmount = () => {
      state.value.totalAmount = state.value.selectedStudents.reduce(
        (sum, s) => sum + Number(s.expenseTypesOptionAmount || 0), 0
      );
    };

    onMounted(async () => {
      try {
        NProgress.start();
        state.value.loadingData = true;

        await getExpenseTypes();

        const res = await axios.get(`/reviewExpenseData/${state.value.expenseId}`);
        state.value.selectedStudents = res.data.students.map(s => {
          const pivot = s.expenses.find(e => e.pivot.expense_id === state.value.expenseId)?.pivot || {};
          return {
            studentId: s.id,
            fname: s.fname,
            mname: s.mname,
            sname: s.sname,
            course_class: s.course?.class || 'N/A',
            student_invoice: s.invoice,
            expenseTypesOption: pivot.expense_type || '',
            expenseTypesOptionName: expenseTypes.value.flatMap(et => et.expense_type_options).find(opt => opt.id === pivot.expense_type)?.name || '',
            expenseTypesOptionAmount: pivot.amount || 0,
            expenses: [{ pivot: pivot }],
          };
        });

        totalAmount();
      } catch (err) {
        notify('Failed to load expense data', 'error');
      } finally {
        NProgress.done();
        state.value.loadingData = false;
      }
    });

    const removeStudentFromList = async (studentId, index) => {
      if (state.value.selectedStudents.length <= 1) {
        showAlert('List cannot be empty', 'You must have at least one student.', { icon: 'error', toast: false });
        return;
      }
      try {
        NProgress.start();
        const response = await axios.post('/removeStudent', { student: studentId, expenseId: state.value.expenseId });
        if (response.status === 200) {
          state.value.selectedStudents.splice(index, 1);
          totalAmount();
          notify('Student removed successfully', 'success');
        }
      } catch {
        notify('Something went wrong', 'error');
      } finally {
        NProgress.done();
      }
    };

    const approveList = async () => {
      try {
        NProgress.start();
        state.value.processing = true;
        const response = await axios.post('/approveList', {
          expenseId: state.value.expenseId,
          approvedAmount: state.value.totalAmount
        });
        if (response.status === 200) {
          notify('List updated successfully', 'success');
          state.value.expenseStatus = response.data.approved ? 1 : 0;
        }
      } catch {
        notify('Something went wrong', 'error');
      } finally {
        NProgress.done();
        state.value.processing = false;
      }
    };

    const notify = (text, icon) =>
      Swal.fire({
        toast: true,
        position: "top-end",
        text,
        icon,
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true
      });

    const showAlert = (title, text, { icon = 'info', toast = true, confirmText = 'OK' } = {}) =>
      Swal.fire({
        icon,
        title,
        text,
        toast,
        position: toast ? 'top-end' : 'center',
        showConfirmButton: !toast,
        confirmButtonText: confirmText,
        timer: toast ? 3000 : undefined
      });

    return {
      state,
      formatter,
      formatDate,
      expenseGroupTypeName,
      removeStudentFromList,
      approveList,
    };
  }
});
app.mount('#expense');
</script>
@endsection
