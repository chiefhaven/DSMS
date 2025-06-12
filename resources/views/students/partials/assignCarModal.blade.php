<div class="modal assignCar fade" tabindex="-1" aria-labelledby="assign-car-modal" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="block block-rounded mb-0">
                <!-- Header -->
                <div class="modal-header block-header bg-primary p-3">
                    <h3 class="block-title text-white fs-4 fw-bold">
                        <i class="fas fa-car me-2"></i>Assign Vehicle
                    </h3>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="block-content p-4">
                    <div class="col-12 mb-4">
                        <div class="form-floating">
                            <select class="form-select border-2 rounded-3 py-3"
                                    id="fleet"
                                    name="fleet"
                                    v-model="fleetRegNumber">
                                <option value="" disabled selected>Select a vehicle</option>
                                <option v-for="option in cars"
                                        :value="option.car_registration_number"
                                        class="py-2">
                                    @{{ option.car_brand_model }} (@{{ option.car_registration_number }})
                                </option>
                            </select>
                            <label for="fleet" class="text-muted px-2">
                                <i class="fas fa-car-side me-2"></i>Available Vehicles
                            </label>
                        </div>
                        <div class="form-text text-end mt-1">Select from available fleet</div>
                    </div>

                    <!-- Footer Buttons -->
                    <div class="d-flex justify-content-end gap-3 pt-2">
                        <button type="button"
                                class="btn btn-primary rounded-pill px-4"
                                @click="assign()">
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