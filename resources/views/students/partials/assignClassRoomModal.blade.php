<div class="modal assignClassRoom fade" tabindex="-1" aria-labelledby="assign-classroom-modal" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="block block-rounded mb-0">
                <!-- Header with gradient background -->
                <div class="block-header bg-gradient-info p-3">
                    <h3 class="block-title text-white fs-4 fw-bold">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Assign Classroom
                    </h3>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="block-content p-4">
                    <div class="col-12 mb-4">
                        <div class="form-floating">
                            <select class="form-select border-2 rounded-3 py-3"
                                    id="classRoom"
                                    name="classRoom"
                                    v-model="classRoom">
                                <option value="" disabled selected>Select a classroom</option>
                                <option v-for="classRoom in classRooms"
                                        :value="classRoom.id"
                                        class="py-2">
                                    @{{ classRoom.name }} <span class="text-muted">(@{{ classRoom.location }})</span>
                                </option>
                            </select>
                            <label for="classRoom" class="text-muted px-2">
                                <i class="fas fa-door-open me-2"></i>Available Classrooms
                            </label>
                        </div>
                        <div class="form-text text-end mt-1">Select from available locations</div>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="d-flex justify-content-end gap-3 pt-2">
                        <button type="button"
                                class="btn btn-primary rounded-pill px-4"
                                @click="assignClassRoom()">
                            <i class="fas fa-check-circle me-2"></i>Assign
                        </button>
                        <button type="button"
                                class="btn btn-outline-secondary rounded-pill px-4"
                                data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>