@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Bulk attendance</h1>
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

<div class="content content-full" id="bulkAttendances">
<div class="row">
    <div class="col-md-12 block-rounded block-bordered">
        <div class="block block-rounded block-themed block-transparent mb-0" style="background-color:#ffffff">
            <div class="block-content">
                <div class="text-center" v-if="loadingData">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <table id="bulkAttendanceTable" class="table table-bordered table-striped table-vcenter w-100">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-center" style="min-width: 7rem;">Actions</th>
                            <th style="min-width: 18rem;">Students</th>
                            <th style="min-width: 18rem;">Entered by</th>
                            <th style="min-width: 10rem;">Description</th>
                            <th style="min-width: 10rem;">Created at</th>
                            <th style="min-width: 10rem;">Updated at</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
<!-- END Hero -->

<script setup>
    const bulkAttendances = createApp({
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
                new bootstrap.Modal(document.getElementById('bulkAttendanceModal')).show();
            };

            const openEditModal = (bulkAttendance) => {
            isEditMode.value = true;
            form.value = {
                    id: bulkAttendance.id,
                    name: bulkAttendance.name,
                    description: bulkAttendance.description,
                    options: bulkAttendance.expense_type_options.map(opt => ({
                        name: opt.name,
                        amount_per_student: opt.amount_per_student,
                        fees_percent_threshhold: opt.fees_percent_threshhold,
                        period_threshold: opt.period_threshold,
                    })),
                is_active: bulkAttendance.is_active == 1 ? true : false,
            };

            console.log(form.value);
            new bootstrap.Modal(document.getElementById('bulkAttendanceModal')).show();
            };

            const submitbulkAttendance = async () => {
            try {
                const url = isEditMode.value
                ? `/api/bulk-attendances/${form.value.id}`
                : `/api/bulk-attendances`;
                const method = isEditMode.value ? 'put' : 'post';
                await axios[method](url, {
                    name: form.value.name,
                    description: form.value.description,
                    options: form.value.options,
                    is_active: form.value.is_active ? 1 : 0,
                });
                showToast(`Expense Type ${isEditMode.value ? 'updated' : 'created'} successfully!`);
                bootstrap.Modal.getInstance(document.getElementById('bulkAttendanceModal')).hide();
                $('#bulkAttendanceTable').DataTable().ajax.reload();
            } catch (error) {
                console.error(error);
                showToast('Something went wrong!', 'error');
            }
            };

            const deletebulkAttendance = async (bulkAttendance) => {
            Swal.fire({
                title: 'Delete expense type?',
                text: 'Are you sure you want to delete this bulk attendance?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Delete',
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        NProgress.start();
                        await axios.delete(`/api/bulk-attendance/${bulkAttendance.id}`);
                            showToast('Bulk attendance deleted.');
                        $('#bulkAttendanceTable').DataTable().ajax.reload();
                    } catch (error) {
                        console.error(error);
                        showToast(error.response?.data?.message || 'An error occurred.', 'error');
                    } finally {
                        NProgress.done();
                        loadingData.value = false;
                    }
                }});
            };

            const getBulkAttendances = () => {
                NProgress.start();
                loadingData.value = true;

                // Destroy any existing DataTable instance
                if ($.fn.DataTable.isDataTable('#bulkAttendanceTable')) {
                    $('#bulkAttendanceTable').DataTable().destroy();
                }

                $('#bulkAttendanceTable').DataTable({
                    serverSide: true,
                    processing: true,
                    scrollCollapse: true,
                    scrollX: true,
                    ajax: async function (data, callback, settings) {
                        try {
                            const response = await axios.get('/api/bulk-attendances', {
                                params: data
                            });
                            callback(response.data);
                        } catch (error) {
                            console.error('Error loading bulk attendances:', error);
                            callback({ data: [], recordsTotal: 0, recordsFiltered: 0 });
                        } finally {
                            loadingData.value = false;
                            NProgress.done();
                        }
                    },
                    columns: [
                        { data: 'actions', className: 'text-center', orderable: false },
                        { data: 'students', className: 'text-wrap', orderable: false, searchable: false },
                        { data: 'entered_by', className: 'text-wrap', orderable: false, searchable: false},
                        { data: 'description', className: 'text-wrap' },
                        { data: 'created_at', className: 'text-wrap', orderable: false, searchable: false},
                        { data: 'updated_at', className: 'text-wrap', orderable: false, searchable: false}

                    ],
                    language: {
                        emptyTable: "No bulk attendance records found."
                    }
                });
            };


            onMounted(() => { getBulkAttendances(); });

            return {
            loadingData, form, isEditMode, addOption, removeOption,
            openCreateModal, openEditModal, submitbulkAttendance, deletebulkAttendance
            };
        },
    });

        window.bulkAttendanceApp = bulkAttendances.mount('#bulkAttendances');

        window.openEditbulkAttendance = el => {
        const bulkAttendance = JSON.parse(el.dataset.bulkAttendance);
        window.bulkAttendanceApp.openEditModal(bulkAttendance);
    };

        window.openDeletebulkAttendance = bulkAttendance => {
        window.bulkAttendanceApp.deletebulkAttendance(bulkAttendance);
    };
</script>
@endsection

