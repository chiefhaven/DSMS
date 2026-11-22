@can('process payroll')
    <div class="tab-pane fade" id="pension-settings-panel" role="tabpanel" aria-labelledby="pension-settings-tab">
        <div class="block block-rounded mb-0">
            <div class="block-header block-header-default">
                <h3 class="block-title">Pension contributions</h3>
            </div>
            <div class="block-content block-content-full">
                <div v-if="pensionSettingsLoadError" class="alert alert-danger" role="alert">@{{ pensionSettingsLoadError }}</div>
                <div v-if="pensionSettingsError" class="alert alert-danger" role="alert">@{{ pensionSettingsError }}</div>
                <div v-if="pensionSettingsLoading" class="text-center text-muted py-4" role="status">
                    <span class="spinner-border spinner-border-sm me-2"></span>Loading pension settings...
                </div>
                <form v-else @submit.prevent="savePensionSettings">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="form-check form-switch mt-4 pt-2">
                                <input id="pension-enabled" class="form-check-input" type="checkbox" role="switch" v-model="pensionSettings.enabled">
                                <label class="form-check-label fw-semibold" for="pension-enabled">Enable pension deductions</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="pension-employee-rate">Employee contribution (%)</label>
                            <input id="pension-employee-rate" type="number" step="0.01" min="0" class="form-control" v-model.number="pensionSettings.employeeRatePercent" placeholder="0">
                            <div class="text-muted fs-sm mt-1">Percentage of basic salary deducted from the employee.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="pension-employer-rate">Employer contribution (%)</label>
                            <input id="pension-employer-rate" type="number" step="0.01" min="0" class="form-control" v-model.number="pensionSettings.employerRatePercent" placeholder="0">
                            <div class="text-muted fs-sm mt-1">Percentage of basic salary contributed by the employer.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary rounded-pill px-3" :disabled="pensionSettingsSaving">
                            <span v-if="pensionSettingsSaving" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                            <i v-else class="fa fa-save me-1" aria-hidden="true"></i> Save settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
