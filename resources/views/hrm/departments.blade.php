@extends('layouts.backend')

@section('content')
    @include('hrm.partials.hrmNav', ['hrmActive' => 'departments'])

    <div class="content content-full" id="hr-departments">
        <div class="block block-rounded">
            <div class="block-header block-header-default d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <h3 class="block-title">HR departments</h3>
                @can('manage hr departments')
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" @click="openAdd">
                        <i class="fa fa-plus me-1"></i> Add department
                    </button>
                @endcan
            </div>
            <div class="block-content block-content-full">
                <div v-if="loadError" class="alert alert-danger" role="alert">@{{ loadError }}</div>
                <div v-if="loading" class="text-center text-muted py-4" role="status">
                    <span class="spinner-border spinner-border-sm me-2"></span>Loading departments...
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter mb-0 fs-sm">
                        <thead class="thead-dark">
                            <tr>
                                <th style="min-width: 12rem;">Name</th>
                                <th>Description</th>
                                @can('manage hr departments')
                                <th class="text-center" style="min-width: 100px;">Actions</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!items.length">
                                <td colspan="3" class="text-center text-muted py-4">No HR departments yet.</td>
                            </tr>
                            <tr v-for="item in items" :key="item.id">
                                <td class="fw-semibold">@{{ item.name }}</td>
                                <td>@{{ item.description || '-' }}</td>
                                @can('manage hr departments')
                                <td class="text-center">
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-sm btn-primary rounded-pill px-3 text-nowrap" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-angle-down me-1" aria-hidden="true"></i> Actions</button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item btn" @click="openEdit(item)"><i class="fa fa-pencil me-3"></i> Edit</a>
                                            <a class="dropdown-item btn text-danger" @click="confirmDelete(item)"><i class="fa fa-trash me-3"></i> Delete</a>
                                        </div>
                                    </div>
                                </td>
                                @endcan
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @can('manage hr departments')
        <div class="modal fade" id="hr-department-modal" tabindex="-1" aria-labelledby="hr-department-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title text-white" id="hr-department-modal-title">@{{ form.id ? 'Edit department' : 'Add department' }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form @submit.prevent="save">
                        <div class="modal-body">
                            <div v-if="formError" class="alert alert-danger" role="alert">@{{ formError }}</div>
                            <div class="mb-3">
                                <label class="form-label" for="hr-department-name">Name</label>
                                <input id="hr-department-name" type="text" class="form-control" v-model="form.name" required maxlength="255">
                                <div v-if="formErrors.name" class="text-danger fs-sm">@{{ formErrors.name[0] }}</div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="hr-department-description">Description</label>
                                <input id="hr-department-description" type="text" class="form-control" v-model="form.description" maxlength="255">
                                <div v-if="formErrors.description" class="text-danger fs-sm">@{{ formErrors.description[0] }}</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                @{{ form.id ? 'Save changes' : 'Add department' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endcan
    </div>

    <script>
        createApp({
            setup() {
                const items = ref([]);
                const loading = ref(true);
                const loadError = ref('');
                const form = ref({ id: null, name: '', description: '' });
                const formErrors = ref({});
                const formError = ref('');
                const saving = ref(false);

                const getModal = () => {
                    const modalElement = document.getElementById('hr-department-modal');
                    return modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;
                };

                const load = async () => {
                    loading.value = true;
                    loadError.value = '';
                    try {
                        const { data } = await axios.get(@json(route('hr-departments.fetch')));
                        items.value = data;
                    } catch (error) {
                        loadError.value = 'Unable to load HR departments. Reload the page to try again.';
                    } finally {
                        loading.value = false;
                    }
                };

                const openAdd = () => {
                    form.value = { id: null, name: '', description: '' };
                    formErrors.value = {};
                    formError.value = '';
                    getModal()?.show();
                };

                const openEdit = (item) => {
                    form.value = { id: item.id, name: item.name, description: item.description || '' };
                    formErrors.value = {};
                    formError.value = '';
                    getModal()?.show();
                };

                const save = async () => {
                    if (saving.value) return;
                    saving.value = true;
                    formErrors.value = {};
                    formError.value = '';
                    try {
                        const payload = { name: form.value.name, description: form.value.description || null };
                        const response = form.value.id
                            ? await axios.put(@json(url('hrm/departments')) + '/' + form.value.id, payload)
                            : await axios.post(@json(route('hr-departments.store')), payload);
                        getModal()?.hide();
                        toast(response.data.message);
                        load();
                    } catch (error) {
                        formErrors.value = error.response?.data?.errors ?? {};
                        formError.value = error.response?.data?.message || 'Unable to save the department. Please try again.';
                    } finally {
                        saving.value = false;
                    }
                };

                const confirmDelete = (item) => {
                    Swal.fire({
                        title: 'Delete department?',
                        text: `"${item.name}" will be removed.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Delete'
                    }).then(async (result) => {
                        if (!result.isConfirmed) return;
                        try {
                            const { data } = await axios.delete(@json(url('hrm/departments')) + '/' + item.id);
                            toast(data.message);
                            load();
                        } catch (error) {
                            Swal.fire({ icon: 'error', title: 'Unable to delete', text: error.response?.data?.message || 'Please try again.' });
                        }
                    });
                };

                const toast = (message) => Swal.fire({
                    icon: 'success', title: message, toast: true, timer: 3000,
                    position: 'top-end', showConfirmButton: false, timerProgressBar: true
                });

                onMounted(() => {
                    axios.defaults.headers.common['X-CSRF-TOKEN'] = $('meta[name="csrf-token"]').attr('content');
                    load();
                });

                return { items, loading, loadError, form, formErrors, formError, saving, openAdd, openEdit, save, confirmDelete };
            }
        }).mount('#hr-departments');
    </script>
@endsection
