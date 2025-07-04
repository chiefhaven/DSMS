@extends('layouts.backend')

@section('content')
<!-- Hero -->
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
      <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Expense types</h1>
      <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">

        @if(Session::has('message'))
        <div class="alert alert-info">
          {{ Session::get('message') }}
        </div>
        @endif

        @role(['superAdmin', 'admin'])
        <div>
          <button
            type="button"
            class="btn btn-primary rounded-pill px-4"
            onclick="window.expenseTypeApp.openCreateModal()"
          >
            <i class="fa fa-file-plus me-3"></i> Add expense type
          </button>
        </div>
        @endrole

      </nav>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="content" id="expenseTypes">
    <div class="block block-rounded block-bordered">
        <div class="content-full">
        <div class="row">
            <!-- Loading Spinner -->
            <div v-if="loadingData" class="d-flex flex-column justify-content-center align-items-center" style="height: 300px;">
                <span class="spinner-border text-primary"></span>
                <p class="mt-3">Loading expense types...</p>
            </div>

            <!-- DataTable -->
            <div v-show="!loadingData">
                <div class="col-md-12 py-4">
                    <div class="m-4 table-responsive">
                    <table id="expenseTypesTable" class="table table-bordered table-striped table-vcenter">
                        <thead class="thead-dark">
                            <tr>
                            <th class="text-center" style="min-width: 7em;">Actions</th>
                            <th style="min-width: 12rem;">Type</th>
                            <th style="min-width: 10rem;">Description</th>
                            <th style="min-width: 18rem;">Options</th>
                            <th class="text-center" style="min-width: 7rem;">Status</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- ONE Modal for Create & Edit -->
  <div class="modal fade" id="expenseTypeModal" tabindex="-1" aria-labelledby="expenseTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form @submit.prevent="submitExpenseType">
          <div class="modal-header">
            <h5 class="modal-title text-white" id="expenseTypeModalLabel">
              @{{ isEditMode ? 'Edit Expense Type' : 'Create Expense Type' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body">
            <!-- Type Name -->
            <div class="mb-3">
              <label for="name" class="form-label">Type Name</label>
              <input v-model="form.name" type="text" class="form-control" id="name" placeholder="Enter expense type name" required>
            </div>

            <!-- Description -->
            <div class="mb-3">
              <label for="description" class="form-label">Description</label>
              <textarea v-model="form.description" class="form-control" id="description" placeholder="Enter description" rows="3"></textarea>
            </div>

            <!-- Options -->
            <div class="mb-3">
                <label class="form-label">Options</label>
                <div v-for="(option, index) in form.options" :key="index" class="row g-2 mb-2 align-items-end">
                    <div class="col">
                        <label class="form-label small">Option name</label>
                        <input v-model="option.name" type="text" class="form-control" placeholder="Option name" required>
                    </div>
                    <div class="col">
                        <label class="form-label small">Amount</label>
                        <input v-model="option.amount_per_student" type="number" min="0" class="form-control" placeholder="Amount per student">
                    </div>
                    <div class="col">
                        <label class="form-label small">Allowable fees %</label>
                        <div class="input-group">
                            <input v-model="option.fees_percent_threshhold" type="number" min="0" max="100" step="0.01" class="form-control" placeholder="Fees %">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col">
                        <label class="form-label small">Selection period</label>
                        <div class="input-group">
                            <input v-model="option.period_threshold" type="number" min="0" max="100" step="0.01" class="form-control" placeholder="Selection period">
                            <span class="input-group-text">Days</span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-danger" @click="removeOption(index)"><i class="fa fa-times"></i></button>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-secondary mt-2" @click="addOption"><i class="fa fa-plus me-1"></i> Add Option</button>
              </div>


            <!-- Status -->
            <div class="mb-3">
              <label class="form-label">Status</label><br>
              <div class="form-check form-switch">
                <input v-model="form.is_active" class="form-check-input" type="checkbox" id="isActive">
                <label class="form-check-label" for="isActive">Active</label>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="submit" class="btn btn-primary rounded-pill px-4">
              @{{ isEditMode ? 'Update' : 'Create' }}
            </button>
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
</div>
<!-- END Main Content -->

<!-- Vue Script -->
<script setup>
    const expenseTypes = createApp({
    setup() {
        const loadingData = ref(false);
        const form = ref({
            name: '',
            description: '',
            options: [],
            is_active: true,
        });
        const isEditMode = ref(false);

        const showToast = (message, icon = 'success') => {
        Swal.fire({
            icon,
            title: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
        };

        const addOption = () => {
            form.value.options.push({ name: '', amount_per_student: 0, fees_percent_threshhold: 0, period_threshold: 0 });
        };

        const removeOption = (index) => {
        form.value.options.splice(index, 1);
        };

        const openCreateModal = () => {
        isEditMode.value = false;
        form.value = { name: '', description: '', options: [], is_active: true };
        new bootstrap.Modal(document.getElementById('expenseTypeModal')).show();
        };

        const openEditModal = (expenseType) => {
        isEditMode.value = true;
        form.value = {
                id: expenseType.id,
                name: expenseType.name,
                description: expenseType.description,
                options: expenseType.expense_type_options.map(opt => ({
                    name: opt.name,
                    amount_per_student: opt.amount_per_student,
                    fees_percent_threshhold: opt.fees_percent_threshhold,
                    period_threshold: opt.period_threshold,
                })),
            is_active: expenseType.is_active == 1 ? true : false,
        };

        console.log(form.value);
        new bootstrap.Modal(document.getElementById('expenseTypeModal')).show();
        };

        const submitExpenseType = async () => {
        try {
            const url = isEditMode.value
            ? `/api/expense-types/${form.value.id}`
            : `/api/expense-types`;
            const method = isEditMode.value ? 'put' : 'post';
            await axios[method](url, {
                name: form.value.name,
                description: form.value.description,
                options: form.value.options,
                is_active: form.value.is_active ? 1 : 0,
            });
            showToast(`Expense Type ${isEditMode.value ? 'updated' : 'created'} successfully!`);
            bootstrap.Modal.getInstance(document.getElementById('expenseTypeModal')).hide();
            $('#expenseTypesTable').DataTable().ajax.reload();
        } catch (error) {
            console.error(error);
            showToast('Something went wrong!', 'error');
        }
        };

        const deleteExpenseType = async (expenseType) => {
        Swal.fire({
            title: 'Delete expense type?',
            text: 'Are you sure you want to delete this expense type?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Delete',
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    NProgress.start();
                    await axios.delete(`/api/expense-types/${expenseType.id}`);
                    showToast('Expense type deleted.');
                    $('#expenseTypesTable').DataTable().ajax.reload();
                } catch (error) {
                    console.error(error);
                    showToast(error.response?.data?.message || 'An error occurred.', 'error');
                } finally {
                    NProgress.done();
                    loadingData.value = false;
                }
            }});
        };

        const getExpenseTypes = () => {
        NProgress.start();
        loadingData.value = true;
        if ($.fn.DataTable.isDataTable('#expenseTypesTable')) {
            $('#expenseTypesTable').DataTable().destroy();
        }
        $('#expenseTypesTable').DataTable({
            serverSide: true,
            processing: true,
            processing: true,
            scrollCollapse: true,
            scrollX: true,
            ajax: async function (data, callback) {
            const response = await axios.get('/api/expense-types', { params: data });
            callback(response.data);
            loadingData.value = false;
            NProgress.done();
            },
            columns: [
            { data: 'actions', className: 'text-center', orderable: false },
            { data: 'type' },
            { data: 'description' },
            { data: 'options', orderable: false, searchable: false },
            { data: 'status', className: 'text-center' },
            ],
        });
        };

        onMounted(() => { getExpenseTypes(); });

        return {
        loadingData, form, isEditMode, addOption, removeOption,
        openCreateModal, openEditModal, submitExpenseType, deleteExpenseType
        };
    },
    });

    window.expenseTypeApp = expenseTypes.mount('#expenseTypes');

    window.openEditExpenseType = el => {
    const expenseType = JSON.parse(el.dataset.expenseType);
    window.expenseTypeApp.openEditModal(expenseType);
    };

    window.openDeleteExpenseType = expenseType => {
    window.expenseTypeApp.deleteExpenseType(expenseType);
    };
</script>
@endsection
