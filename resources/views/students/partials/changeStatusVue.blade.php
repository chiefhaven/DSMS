@role(['superAdmin', 'admin', 'instructor'])
<!-- Modal Backdrop -->
<div v-if="showStatusChangeModal" class="modal-backdrop fade show" style="background-color: rgba(0,0,0,0.5);"></div>

<!-- Modal -->
<div class="modal fade show d-block"
     v-if="showStatusChangeModal"
     tabindex="-1"
     aria-labelledby="statusModalLabel"
     aria-modal="true"
     role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Header -->
            <div class="modal-header bg-gradient-primary p-4">
                <h5 class="modal-title text-white fs-5 fw-bold">
                    <i class="fas fa-user-edit me-2"></i>Update Student Status
                </h5>
                <button type="button" class="btn-close btn-close-white" @click="closeStatusChangeModal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body p-4">
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <img :src="studentAvatar || '/images/default-avatar.jpg'"
                             class="rounded-circle"
                             width="50"
                             height="50"
                             alt="Student Avatar">
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="mb-0">@{{ studentName }}</h6>
                        <small class="text-muted">ID: @{{ studentId }}</small>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="studentStatus" class="form-label text-muted mb-2">
                        <i class="fas fa-user-tag me-2"></i>Select New Status
                    </label>
                    <select v-model="studentStatus"
                            class="form-select border-2 rounded-3 py-3"
                            id="studentStatus"
                            style="background-image: none;">
                        @role(['superAdmin', 'admin'])
                        <option value="Pending" class="text-warning">
                            <i class="fas fa-clock me-2"></i> Pending
                        </option>
                        <option value="In progress" class="text-info">
                            <i class="fas fa-spinner me-2"></i> In Progress
                        </option>
                        @endrole
                        <option value="Finished" class="text-success">
                            <i class="fas fa-check-circle me-2"></i> Finished
                        </option>
                    </select>
                    <div class="form-text mt-2">Current status will be updated immediately</div>
                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer border-0 pt-3">
                <button type="button"
                        class="btn btn-outline-secondary rounded-pill px-4"
                        @click="closeStatusChangeModal">
                    <i class="fas fa-times me-2"></i> Cancel
                </button>
                <button type="button"
                        class="btn btn-primary rounded-pill px-4"
                        @click="confirmChangeStatus"
                        :disabled="isUpdating">
                    <template v-if="isUpdating">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Updating...
                    </template>
                    <template v-else>
                        <i class="fas fa-save me-2"></i> Confirm Update
                    </template>
                </button>
            </div>
        </div>
    </div>
</div>
@endrole