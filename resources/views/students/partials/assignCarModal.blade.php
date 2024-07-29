<div class="modal assignCar" tabindex="-1" aria-labelledby="new-registration" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-themed block-transparent mb-0">
                <div class="block-header bg-primary-dark">
                    <h3 class="block-title">Assign Car</h3>
                    <div class="block-options">
                    <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-fw fa-times"></i>
                    </button>
                    </div>
                </div>
                <div class="block-content">
                    <div class="col-12 form-floating mb-4">
                        <select class="form-select" id="fleet" name="fleet" v-model="fleetRegNumber">
                            <option v-for="option in cars" :value="option.car_registration_number">
                                @{{ option.car_brand_model }} (@{{ option.car_registration_number }})
                            </option>
                        </select>
                        <label class="px-4" for="district">Select a car</label>
                    </div>
                    <div class="block-content block-content-full text-end bg-body">
                        <button type="submit" class="btn btn-primary" @click="assign()">Assign</button> &nbsp;
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
