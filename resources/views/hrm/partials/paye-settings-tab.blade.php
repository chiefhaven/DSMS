@can('process payroll')
    <div class="tab-pane fade {{ auth()->user()->cannot('view salary grades') ? 'show active' : '' }}" id="paye-settings-panel" role="tabpanel" aria-labelledby="paye-settings-tab">
        <div class="block block-rounded mb-0">
            <div class="block-header block-header-default">
                <h3 class="block-title">PAYE brackets</h3>
            </div>
            <div class="block-content block-content-full">
                <div v-if="payeSettingsLoadError" class="alert alert-danger" role="alert">@{{ payeSettingsLoadError }}</div>
                <div v-if="payeSettingsError" class="alert alert-danger" role="alert">@{{ payeSettingsError }}</div>
                <div v-if="payeSettingsLoading" class="text-center text-muted py-4" role="status">
                    <span class="spinner-border spinner-border-sm me-2"></span>Loading PAYE settings...
                </div>
                <form v-else @submit.prevent="savePayeSettings">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="form-check form-switch mt-4 pt-2">
                                <input id="paye-enabled" class="form-check-input" type="checkbox" role="switch" v-model="payeSettings.enabled">
                                <label class="form-check-label fw-semibold" for="paye-enabled">Enable PAYE calculations</label>
                            </div>
                            <div class="text-muted fs-sm mt-1">When disabled, PAYE is not deducted even if an employee is marked as PAYE applicable.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="paye-flat-rate">Flat rate (%)</label>
                            <input id="paye-flat-rate" type="number" step="0.01" min="0" class="form-control" v-model.number="payeSettings.ratePercent" placeholder="0">
                            <div class="text-muted fs-sm mt-1">Used when no brackets are configured. Brackets take precedence when added.</div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h4 class="fs-sm fw-semibold mb-0">Brackets</h4>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" @click="addPayeBand">
                            <i class="fa fa-plus me-1" aria-hidden="true"></i> Add bracket
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-vcenter fs-sm mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th style="min-width: 12rem;">Up to amount (K)</th>
                                    <th style="min-width: 10rem;">Rate (%)</th>
                                    <th class="text-center" style="width: 110px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!payeSettings.bands.length">
                                    <td colspan="3" class="text-center text-muted py-3">No brackets configured. The flat rate will be used.</td>
                                </tr>
                                <tr v-for="(band, index) in payeSettings.bands" :key="index">
                                    <td>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                               v-model.number="band.upTo" :placeholder="index === payeSettings.bands.length - 1 ? 'Leave blank for no limit' : ''">
                                        <div class="text-muted fs-sm mt-1">Leave blank for the final/open-ended bracket.</div>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                                               v-model.number="band.ratePercent" placeholder="0" required>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-alt-danger" @click="removePayeBand(index)" aria-label="Remove bracket">
                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted fs-sm mt-2">
                        Brackets are applied progressively in order (e.g. first K100,000 at 0%, next portion at 25%). Rates are percentages of taxable gross.
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-primary rounded-pill px-3" :disabled="payeSettingsSaving">
                            <span v-if="payeSettingsSaving" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                            <i v-else class="fa fa-save me-1" aria-hidden="true"></i> Save settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
