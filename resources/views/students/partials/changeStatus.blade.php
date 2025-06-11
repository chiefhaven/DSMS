@role(['superAdmin', 'admin', 'instructor'])
<div class="modal fade" id="change-status" tabindex="-1" aria-labelledby="statusModalLabel" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Header -->
            <div class="block-header modal-header bg-gradient-warning p-4">
                <h5 class="modal-title text-white fs-5 fw-bold">
                    <i class="fas fa-user-edit me-2"></i>Update Student Status
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body -->
            <div class="modal-body p-4">
                <form id="statusForm" action="{{ url('/updateStudentStatus', $student->id) }}" method="post">
                    @csrf

                    <!-- Status Selection -->
                    <div class="mb-4">
                        <label for="status" class="form-label text-muted mb-3">
                            <i class="fas fa-user-tag me-2"></i>Current Status
                        </label>
                        <select class="form-select border-2 rounded-3 py-3"
                                id="status"
                                name="status"
                                style="background-image: none; padding-right: 2.5rem;">
                            @role(['superAdmin'])
                            <option value="Pending" {{ $student->status == 'Pending' ? 'selected' : '' }}>
                                <i class="fas fa-clock me-2"></i>Pending
                            </option>
                            <option value="In progress" {{ $student->status == 'In progress' ? 'selected' : '' }}>
                                <i class="fas fa-spinner me-2"></i>In Progress
                            </option>
                            @endrole
                            <option value="Finished" {{ $student->status == 'Finished' ? 'selected' : '' }}>
                                <i class="fas fa-check-circle me-2"></i>Finished
                            </option>
                        </select>
                        <div class="form-text mt-2">Select the new status for this student</div>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="modal-footer border-0 pt-3 px-0">
                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            <i class="fas fa-save me-2"></i>Update
                        </button>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endrole