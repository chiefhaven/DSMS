@role(['superAdmin', 'admin', 'instructor', 'financeAdmin'])
<!-- Modal Backdrop -->
<div v-if="showStatusChangeModal" class="modal-backdrop fade show" style="background-color: rgba(0,0,0,0.5);"></div>

<!-- Modal -->
<div class="modal fade show d-block"
     v-if="showStatusChangeModal"
     tabindex="-1"
     aria-labelledby="statusModalLabel"
     aria-modal="true"
     role="dialog"
     style="overflow-y: auto;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <!-- Header -->
            <div class="modal-header bg-gradient-primary p-4" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <img :src="'/media/avatars/avatar2.jpg' || '/media/avatars/avatar2.jpg'"
                             class="rounded-circle border border-3 border-white"
                             width="50"
                             height="50"
                             alt="Student Avatar">
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h5 class="modal-title text-white mb-0">
                            <i class="fas fa-user-edit me-2"></i>Update Status for @{{ studentName }}
                        </h5>
                        <small class="text-white-50">ID: @{{ studentId }}</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" @click="closeStatusChangeModal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body p-4">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label for="studentStatus" class="form-label fw-bold text-muted mb-3">
                            <i class="fas fa-user-tag me-2"></i>Select New Status
                        </label>
                        <select v-model="studentStatus"
                                class="form-select border-2 rounded-3 py-3"
                                id="studentStatus"
                                style="background-image: none; border-color: #dee2e6;">
                            @role(['superAdmin', 'admin'])
                            <option value="Pending" class="text-warning">
                                <i class="fas fa-clock me-2"></i> Pending
                            </option>
                            <option value="In progress" class="text-primary">
                                <i class="fas fa-spinner me-2"></i> In Progress
                            </option>
                            @endrole
                            <option value="Finished" class="text-success">
                                <i class="fas fa-check-circle me-2"></i> Finished
                            </option>
                            <option value="Cancelled" class="text-danger">
                                <i class="fas fa-ban me-2"></i> Cancelled
                            </option>
                        </select>
                        <div class="form-text mt-2">This will immediately update student status</div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="card-title text-muted mb-3">
                                    <i class="fas fa-info-circle me-2"></i>Status Information
                                </h6>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2"><span class="badge bg-warning me-2">Pending</span> Registration complete, not started</li>
                                    <li class="mb-2"><span class="badge bg-primary me-2">In Progress</span> Currently active in program</li>
                                    <li class="mb-2"><span class="badge bg-success me-2">Finished</span> Completed all requirements</li>
                                    <li><span class="badge bg-danger me-2">Cancelled</span> Withdrawn from program</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4" v-if="studentStatus === 'Finished'">
                    <label class="form-label fw-bold text-muted mb-3">
                        <i class="fas fa-award me-2"></i>Completion Details
                    </label>
                    <textarea class="form-control border-2 rounded-3"
                              v-model="completionNotes"
                              placeholder="Optional notes about completion..."
                              rows="3"></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer border-0 pt-3 bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                <button type="button"
                        class="btn btn-primary rounded-pill px-4"
                        @click="confirmChangeStatus"
                        :disabled="isUpdating">
                    <div v-if="isUpdating">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Updating...
                    </div>
                    <div v-else>
                        <i class="fas fa-save me-2"></i> Update
                    </div>
                </button>
                <button type="button"
                        class="btn btn-outline-secondary rounded-pill px-4"
                        @click="closeStatusChangeModal">
                    <i class="fas fa-times me-2"></i> Cancel
                </button>
            </div>
        </div>
    </div>
</div>
<div v-else>
</div>
@endrole
