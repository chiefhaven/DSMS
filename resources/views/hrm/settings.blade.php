@extends('layouts.backend')

@section('content')
    @include('hrm.partials.hrmNav', ['hrmActive' => 'settings'])

    <div class="content content-full" id="hrm-settings">
        <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between mb-3">
            <h2 class="fs-4 fw-semibold mb-0">HRM Settings</h2>
        </div>

        <div class="block block-rounded">
            <ul class="nav nav-tabs nav-tabs-block" role="tablist" aria-label="HRM settings">
                @can('view salary grades')
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ auth()->user()->can('view salary grades') ? 'active' : '' }}"
                                id="salary-grades-tab" data-bs-toggle="tab" data-bs-target="#salary-grades-panel"
                                type="button" role="tab" aria-controls="salary-grades-panel"
                                aria-selected="{{ auth()->user()->can('view salary grades') ? 'true' : 'false' }}">
                            <i class="fa fa-layer-group me-2"></i>Salary Grades
                        </button>
                    </li>
                @endcan
                @can('process payroll')
                    <li class="nav-item" role="presentation">
                        <button class="nav-link {{ auth()->user()->can('view salary grades') ? '' : 'active' }}"
                                id="paye-settings-tab" data-bs-toggle="tab" data-bs-target="#paye-settings-panel"
                                type="button" role="tab" aria-controls="paye-settings-panel"
                                aria-selected="{{ auth()->user()->can('view salary grades') ? 'false' : 'true' }}">
                            <i class="fa fa-cog me-2"></i>PAYE Settings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                                id="pension-settings-tab" data-bs-toggle="tab" data-bs-target="#pension-settings-panel"
                                type="button" role="tab" aria-controls="pension-settings-panel"
                                aria-selected="false">
                            <i class="fa fa-piggy-bank me-2"></i>Pension
                        </button>
                    </li>
                @endcan
            </ul>
            <div class="block-content tab-content">
                @can('view salary grades')
                    @include('hrm.partials.salary-grades-tab')
                @endcan
                @include('hrm.partials.paye-settings-tab')
                @include('hrm.partials.pension-settings-tab')
            </div>
        </div>
    </div>
@endsection

@section('js_after')
    <script>
        (() => {
            const { createApp, ref, onMounted } = Vue;
            createApp({
            setup() {
                @can('view salary grades')
                const salaryGrades = ref([]);
                const salaryGradesLoading = ref(true);
                const salaryGradesLoadError = ref('');
                const salaryGradeForm = ref({ id: null, code: '', name: '', description: '', basic_salary: null });
                const salaryGradeFormErrors = ref({});
                const salaryGradeFormError = ref('');
                const salaryGradeSaving = ref(false);

                const getModal = (id) => {
                    const modalElement = document.getElementById(id);
                    return modalElement ? bootstrap.Modal.getOrCreateInstance(modalElement) : null;
                };

                const toast = (message) => Swal.fire({
                    icon: 'success', title: message, toast: true, timer: 3000,
                    position: 'top-end', showConfirmButton: false, timerProgressBar: true
                });

                const emptySalaryGradeForm = () => ({ id: null, code: '', name: '', description: '', basic_salary: null });

                const loadSalaryGrades = async () => {
                    salaryGradesLoading.value = true;
                    salaryGradesLoadError.value = '';
                    try {
                        const { data } = await axios.get(@json(route('salary-grades.fetch')));
                        salaryGrades.value = data;
                    } catch (error) {
                        salaryGradesLoadError.value = 'Unable to load salary grades. Reload the page to try again.';
                    } finally {
                        salaryGradesLoading.value = false;
                    }
                };

                const openAddSalaryGrade = () => {
                    salaryGradeForm.value = emptySalaryGradeForm();
                    salaryGradeFormErrors.value = {};
                    salaryGradeFormError.value = '';
                    getModal('salary-grade-modal')?.show();
                };

                const openEditSalaryGrade = (item) => {
                    salaryGradeForm.value = {
                        id: item.id,
                        code: item.code ?? '',
                        name: item.name ?? '',
                        description: item.description ?? '',
                        basic_salary: item.basic_salary !== null && item.basic_salary !== undefined ? Number(item.basic_salary) : null
                    };
                    salaryGradeFormErrors.value = {};
                    salaryGradeFormError.value = '';
                    getModal('salary-grade-modal')?.show();
                };

                const saveSalaryGrade = async () => {
                    if (salaryGradeSaving.value) return;
                    salaryGradeSaving.value = true;
                    salaryGradeFormErrors.value = {};
                    salaryGradeFormError.value = '';
                    try {
                        const payload = {
                            code: salaryGradeForm.value.code,
                            name: salaryGradeForm.value.name,
                            description: salaryGradeForm.value.description || null,
                            basic_salary: salaryGradeForm.value.basic_salary
                        };
                        const response = salaryGradeForm.value.id
                            ? await axios.put(@json(url('hrm/salary-grades')) + '/' + salaryGradeForm.value.id, payload)
                            : await axios.post(@json(route('salary-grades.store')), payload);
                        toast(response.data.message);
                        bootstrap.Modal.getInstance(document.getElementById('salary-grade-modal'))?.hide();
                        loadSalaryGrades();
                    } catch (error) {
                        salaryGradeFormErrors.value = error.response?.data?.errors ?? {};
                        salaryGradeFormError.value = error.response?.data?.message || 'Unable to save the salary grade. Please try again.';
                    } finally {
                        salaryGradeSaving.value = false;
                    }
                };

                const confirmDeleteSalaryGrade = (item) => {
                    Swal.fire({
                        title: 'Delete salary grade?',
                        text: item.name + ' will be removed permanently.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete it',
                        confirmButtonColor: '#d33'
                    }).then(async (result) => {
                        if (result.isConfirmed) {
                            try {
                                await axios.delete(@json(url('hrm/salary-grades')) + '/' + item.id);
                                toast('Salary grade deleted');
                                loadSalaryGrades();
                            } catch (error) {
                                Swal.fire({ icon: 'error', title: 'Unable to delete', text: error.response?.data?.message || 'Please try again.' });
                            }
                        }
                    });
                };

                const formatSalaryAmount = (value) => Number(value || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                @endcan

                @can('process payroll')
                const payeConfig = ref(@json($payeConfig ?? config('payroll.paye')));
                const payeSettingsLoading = ref(false);
                const payeSettingsSaving = ref(false);
                const payeSettingsError = ref('');
                const payeSettingsLoadError = ref('');
                const payeSettings = ref({ enabled: true, ratePercent: 0, bands: [] });

                function payeSettingsFormFromConfig(config) {
                    const safe = config || {};
                    const bands = Array.isArray(safe.bands) ? safe.bands : [];
                    return {
                        enabled: !!(safe.enabled ?? true),
                        ratePercent: Number(safe.rate || 0) * 100,
                        bands: bands.map((band) => ({
                            upTo: band.up_to === null || band.up_to === undefined ? null : Number(band.up_to),
                            ratePercent: Number(band.rate || 0) * 100
                        }))
                    };
                }

                const addPayeBand = () => {
                    payeSettings.value.bands.push({ upTo: null, ratePercent: 0 });
                };

                const removePayeBand = (index) => {
                    payeSettings.value.bands.splice(index, 1);
                };

                const normalizePayeSettingsPayload = () => {
                    const bands = (payeSettings.value.bands || [])
                        .map((band) => ({
                            up_to: band.upTo === '' || band.upTo === null || band.upTo === undefined ? null : Number(band.upTo),
                            rate: Number(band.ratePercent || 0) / 100
                        }))
                        .filter((band) => !(band.up_to === null && (payeSettings.value.bands || []).filter((b) => b.upTo === '' || b.upTo === null || b.upTo === undefined).length > 1 && band.rate === 0 && (payeSettings.value.bands || []).length === 0));

                    return {
                        enabled: !!payeSettings.value.enabled,
                        rate: Number(payeSettings.value.ratePercent || 0) / 100,
                        bands
                    };
                };

                const loadPayeSettings = async () => {
                    payeSettingsLoading.value = true;
                    payeSettingsLoadError.value = '';
                    try {
                        const { data } = await axios.get(@json(route('payroll.paye-settings')));
                        payeConfig.value = data;
                        payeSettings.value = payeSettingsFormFromConfig(data);
                    } catch (error) {
                        payeSettingsLoadError.value = error.response?.data?.message || 'Unable to load PAYE settings.';
                    } finally {
                        payeSettingsLoading.value = false;
                    }
                };

                const savePayeSettings = async () => {
                    if (payeSettingsSaving.value) return;
                    payeSettingsSaving.value = true;
                    payeSettingsError.value = '';
                    try {
                        const payload = normalizePayeSettingsPayload();
                        const { data } = await axios.put(@json(route('payroll.paye-settings.update')), payload);
                        if (data?.paye) {
                            payeConfig.value = data.paye;
                            payeSettings.value = payeSettingsFormFromConfig(data.paye);
                        }
                        Swal.fire({ icon: 'success', title: data?.message || 'PAYE settings saved.', toast: true, timer: 2500, position: 'top-end', showConfirmButton: false });
                    } catch (error) {
                        payeSettingsError.value = error.response?.data?.message || 'Unable to save PAYE settings. Please try again.';
                    } finally {
                        payeSettingsSaving.value = false;
                    }
                };

                const pensionConfig = ref(@json($pensionConfig ?? \App\Models\Setting::pensionConfig()));
                const pensionSettingsLoading = ref(false);
                const pensionSettingsSaving = ref(false);
                const pensionSettingsError = ref('');
                const pensionSettingsLoadError = ref('');
                const pensionSettings = ref({ enabled: true, employeeRatePercent: 5, employerRatePercent: 10 });

                function pensionSettingsFormFromConfig(config) {
                    const safe = config || {};
                    return {
                        enabled: !!(safe.enabled ?? true),
                        employeeRatePercent: Number(safe.employee_rate || 0) * 100,
                        employerRatePercent: Number(safe.employer_rate || 0) * 100
                    };
                }

                const normalizePensionSettingsPayload = () => ({
                    enabled: !!pensionSettings.value.enabled,
                    employee_rate: Number(pensionSettings.value.employeeRatePercent || 0) / 100,
                    employer_rate: Number(pensionSettings.value.employerRatePercent || 0) / 100
                });

                const loadPensionSettings = async () => {
                    pensionSettingsLoading.value = true;
                    pensionSettingsLoadError.value = '';
                    try {
                        const { data } = await axios.get(@json(route('payroll.pension-settings')));
                        pensionConfig.value = data;
                        pensionSettings.value = pensionSettingsFormFromConfig(data);
                    } catch (error) {
                        pensionSettingsLoadError.value = error.response?.data?.message || 'Unable to load pension settings.';
                    } finally {
                        pensionSettingsLoading.value = false;
                    }
                };

                const savePensionSettings = async () => {
                    if (pensionSettingsSaving.value) return;
                    pensionSettingsSaving.value = true;
                    pensionSettingsError.value = '';
                    try {
                        const payload = normalizePensionSettingsPayload();
                        const { data } = await axios.put(@json(route('payroll.pension-settings.update')), payload);
                        if (data?.pension) {
                            pensionConfig.value = data.pension;
                            pensionSettings.value = pensionSettingsFormFromConfig(data.pension);
                        }
                        Swal.fire({ icon: 'success', title: data?.message || 'Pension settings saved.', toast: true, timer: 2500, position: 'top-end', showConfirmButton: false });
                    } catch (error) {
                        pensionSettingsError.value = error.response?.data?.message || 'Unable to save pension settings. Please try again.';
                    } finally {
                        pensionSettingsSaving.value = false;
                    }
                };
                @endcan

                onMounted(() => {
                    @can('view salary grades')
                        loadSalaryGrades();
                    @endcan
                    @can('process payroll')
                        payeSettings.value = payeSettingsFormFromConfig(payeConfig.value);
                        loadPayeSettings();
                        pensionSettings.value = pensionSettingsFormFromConfig(pensionConfig.value);
                        loadPensionSettings();
                    @endcan
                });

                return {
                    @can('view salary grades')
                    salaryGrades, salaryGradesLoading, salaryGradesLoadError,
                    salaryGradeForm, salaryGradeFormErrors, salaryGradeFormError, salaryGradeSaving,
                    openAddSalaryGrade, openEditSalaryGrade, saveSalaryGrade,
                    confirmDeleteSalaryGrade, formatSalaryAmount,
                    @endcan
                    @can('process payroll')
                    payeSettings, payeSettingsLoading, payeSettingsSaving, payeSettingsError, payeSettingsLoadError,
                    addPayeBand, removePayeBand, loadPayeSettings, savePayeSettings,
                    pensionSettings, pensionSettingsLoading, pensionSettingsSaving, pensionSettingsError, pensionSettingsLoadError,
                    loadPensionSettings, savePensionSettings,
                    @endcan
                };
            }
        }).mount('#hrm-settings');

        document.addEventListener('DOMContentLoaded', () => {
            const hash = window.location.hash;
            const salaryGradesTab = document.getElementById('salary-grades-tab');
            const payeSettingsTab = document.getElementById('paye-settings-tab');
            const pensionSettingsTab = document.getElementById('pension-settings-tab');
            if (hash === '#paye-settings-tab' && payeSettingsTab) {
                bootstrap.Tab.getOrCreateInstance(payeSettingsTab).show();
                return;
            }
            if (hash === '#pension-settings-tab' && pensionSettingsTab) {
                bootstrap.Tab.getOrCreateInstance(pensionSettingsTab).show();
                return;
            }
            if (hash === '#salary-grades-tab' && salaryGradesTab) {
                bootstrap.Tab.getOrCreateInstance(salaryGradesTab).show();
                return;
            }
            if (salaryGradesTab) {
                bootstrap.Tab.getOrCreateInstance(salaryGradesTab).show();
            } else if (payeSettingsTab) {
                bootstrap.Tab.getOrCreateInstance(payeSettingsTab).show();
            } else if (pensionSettingsTab) {
                bootstrap.Tab.getOrCreateInstance(pensionSettingsTab).show();
            }
        });
        })();
    </script>
@endsection
