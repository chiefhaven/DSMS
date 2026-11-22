<div class="tab-pane fade {{ auth()->user()->can('view salary grades') ? 'show active' : '' }}"
     id="salary-grades-panel" role="tabpanel" aria-labelledby="salary-grades-tab">
    <div class="block block-rounded mb-0">
        <div class="block-header block-header-default d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <h3 class="block-title">Salary Grades</h3>
            @can('manage salary grades')
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" @click="openAddSalaryGrade">
                    <i class="fa fa-plus me-1"></i> Add salary grade
                </button>
            @endcan
        </div>
        <div class="block-content block-content-full">
            <div v-if="salaryGradesLoadError" class="alert alert-danger" role="alert">@{{ salaryGradesLoadError }}</div>
            <div v-if="salaryGradesLoading" class="text-center text-muted py-4" role="status">
                <span class="spinner-border spinner-border-sm me-2"></span>Loading salary grades...
            </div>
            <div v-else class="table-responsive">
                <table class="table table-bordered table-striped table-vcenter mb-0 fs-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th style="min-width: 8rem;">Code</th>
                            <th style="min-width: 12rem;">Name</th>
                            <th>Description</th>
                            <th class="text-end" style="min-width: 9rem;">Basic salary</th>
                            @can('manage salary grades')
                                <th class="text-center" style="min-width: 100px;">Actions</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="!salaryGrades.length">
                            <td :colspan="@can('manage salary grades') 5 @else 4 @endcan" class="text-center text-muted py-4">
                                No salary grades yet.
                            </td>
                        </tr>
                        <tr v-for="item in salaryGrades" :key="item.id">
                            <td class="fw-semibold">@{{ item.code }}</td>
                            <td>@{{ item.name }}</td>
                            <td>@{{ item.description || '-' }}</td>
                            <td class="text-end">K@{{ formatSalaryAmount(item.basic_salary) }}</td>
                            @can('manage salary grades')
                                <td class="text-center">
                                    <div class="dropdown d-inline-block">
                                        <button class="btn btn-sm btn-primary rounded-pill px-3 text-nowrap" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fa fa-angle-down me-1" aria-hidden="true"></i> Actions
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item btn" @click="openEditSalaryGrade(item)">
                                                <i class="fa fa-pencil me-3"></i> Edit
                                            </a>
                                            <a class="dropdown-item btn text-danger" @click="confirmDeleteSalaryGrade(item)">
                                                <i class="fa fa-trash me-3"></i> Delete
                                            </a>
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

    @can('manage salary grades')
        <div class="modal fade" id="salary-grade-modal" tabindex="-1" aria-labelledby="salary-grade-modal-title" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-white" id="salary-grade-modal-title">
                            @{{ salaryGradeForm.id ? 'Edit salary grade' : 'Add salary grade' }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form @submit.prevent="saveSalaryGrade">
                        <div class="modal-body">
                            <div v-if="salaryGradeFormError" class="alert alert-danger" role="alert">@{{ salaryGradeFormError }}</div>
                            <div class="mb-3">
                                <label class="form-label" for="salary-grade-code">Code</label>
                                <input id="salary-grade-code" type="text" class="form-control" v-model.trim="salaryGradeForm.code" required maxlength="50">
                                <div v-if="salaryGradeFormErrors.code" class="text-danger fs-sm">@{{ salaryGradeFormErrors.code[0] }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="salary-grade-name">Name</label>
                                <input id="salary-grade-name" type="text" class="form-control" v-model.trim="salaryGradeForm.name" required maxlength="255">
                                <div v-if="salaryGradeFormErrors.name" class="text-danger fs-sm">@{{ salaryGradeFormErrors.name[0] }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="salary-grade-description">Description</label>
                                <input id="salary-grade-description" type="text" class="form-control" v-model.trim="salaryGradeForm.description" maxlength="255">
                                <div v-if="salaryGradeFormErrors.description" class="text-danger fs-sm">@{{ salaryGradeFormErrors.description[0] }}</div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="salary-grade-salary">Basic salary (K)</label>
                                <input id="salary-grade-salary" type="number" step="0.01" min="0" class="form-control" v-model.number="salaryGradeForm.basic_salary" required>
                                <div v-if="salaryGradeFormErrors.basic_salary" class="text-danger fs-sm">@{{ salaryGradeFormErrors.basic_salary[0] }}</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="salaryGradeSaving">
                                <span v-if="salaryGradeSaving" class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>
                                @{{ salaryGradeForm.id ? 'Save changes' : 'Add salary grade' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>
