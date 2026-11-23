@extends('layouts.backend')

@section('content')
    @include('hrm.partials.hrmNav', ['hrmActive' => 'designations'])

    <div class="content content-full" id="designations">
        <div class="block block-rounded">
            <div class="block-header block-header-default d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <h3 class="block-title">Designations</h3>
                @can('manage designations')
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" @click="openAdd">
                        <i class="fa fa-plus me-1"></i> Add designation
                    </button>
                @endcan
            </div>
            <div class="block-content block-content-full">
                <div v-if="loadError" class="alert alert-danger" role="alert">@{{ loadError }}</div>
                <div v-if="loading" class="text-center text-muted py-4" role="status">
                    <span class="spinner-border spinner-border-sm me-2"></span>Loading designations...
                </div>
                <div v-else class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter mb-0 fs-sm">
                        <thead class="thead-dark">
                            <tr>
                                <th style="min-width: 12rem;">Name</th>
                                <th style="min-width: 12rem;">Department</th>
                                <th>Description</th>
                                @can('manage designations')
                                <th class="text-center" style="min-width: 100px;">Actions</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!items.length">
                                <td :colspan="@can('manage designations') 4 @else 3 @endcan" class="text-center text-muted py-4">No designations yet.</td>
                            </tr>
                            <tr v-for="item in items" :key="item.id">
                                <td class="fw-semibold">@{{ item.name }}</td>
                                <td>@{{ item.hr_department?.name || '-' }}</td>
                                <td>@{{ item.description || '-' }}</td>
                                @can('manage designations')
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

        @can('manage designations')
        <div class="modal fade" id="designation-modal" tabindex="-1" aria-labelledby="designation-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title text-white" id="designation-modal-title">@{{ form.id ? 'Edit designation' : 'Add designation' }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form @submit.prevent="save">
                        <div class="modal-body">
                            <div v-if="formError" class="alert alert-danger" role="alert">@{{ formError }}</div>
                            <div class="mb-3">
                                <label class="form-label" for="designation-name">Name</label>
                                <input id="designation-name" type="text" class="form-control" v-model="form.name" required maxlength="255">
                                <div v-if="formErrors.name" class="text-danger fs-sm">@{{ formErrors.name[0] }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="designation-department">Department</label>
                                <select id="designation-department" class="form-select" v-model="form.hr_department_id" required>
                                    <option value="">Select department</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                                <div v-if="formErrors.hr_department_id" class="text-danger fs-sm">@{{ formErrors.hr_department_id[0] }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="designation-description">Description</label>
                                <input id="designation-description" type="text" class="form-control" v-model="form.description" maxlength="255">
                                <div v-if="formErrors.description" class="text-danger fs-sm">@{{ formErrors.description[0] }}</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                @{{ form.id ? 'Save changes' : 'Add designation' }}
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
                const form = ref({ id: null, name: '', description: '', hr_department_id: '' });
                const formErrors = ref({});
                const formError = ref('');
                const saving = ref(false);

                const getModal = () => {
                    const modalElement = document.getElementById('designation-modal');
                    return modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;
                };

                const load = async () => {
                    loading.value = true;
                    loadError.value = '';
                    try {
                        const { data } = await axios.get(@json(route('designations.fetch')));
                        items.value = data;
                    } catch (error) {
                        loadError.value = 'Unable to load designations. Reload the page to try again.';
                    } finally {
                        loading.value = false;
                    }
                };

                const openAdd = () => {
                    form.value = { id: null, name: '', description: '', hr_department_id: '' };
                    formErrors.value = {};
                    formError.value = '';
                    getModal()?.show();
                };

                const openEdit = (item) => {
                    form.value = {
                        id: item.id,
                        name: item.name,
                        description: item.description || '',
                        hr_department_id: item.hr_department_id || ''
                    };
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
                        const payload = {
                            name: form.value.name,
                            description: form.value.description || null,
                            hr_department_id: form.value.hr_department_id
                        };
                        const response = form.value.id
                            ? await axios.put(@json(url('hrm/designations')) + '/' + form.value.id, payload)
                            : await axios.post(@json(route('designations.store')), payload);
                        getModal()?.hide();
                        toast(response.data.message);
                        load();
                    } catch (error) {
                        formErrors.value = error.response?.data?.errors ?? {};
                        formError.value = error.response?.data?.message || 'Unable to save the designation. Please try again.';
                    } finally {
                        saving.value = false;
                    }
                };

                const confirmDelete = (item) => {
                    Swal.fire({
                        title: 'Delete designation?',
                        text: `"${item.name}" will be removed.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        confirmButtonText: 'Delete'
                    }).then(async (result) => {
                        if (!result.isConfirmed) return;
                        try {
                            const { data } = await axios.delete(@json(url('hrm/designations')) + '/' + item.id);
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
        }).mount('#designations');
    </script>
@endsection
