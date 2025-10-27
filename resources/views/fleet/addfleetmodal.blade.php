<div class="modal fade" id="modal-block-addfleet" tabindex="-1" aria-labelledby="modal-block-addfleet" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="block block-rounded block-themed block-transparent mb-0">
                <div class="block-header modal-header bg-primary">
                    <h3 class="block-title text-white">
                        <i class="fa fa-car me-2"></i> Add New Vehicle to Fleet
                    </h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option text-white" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="block-content py-3 px-4">
                    <form id="addFleetForm" action="{{ url('/storefleet') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <!-- Vehicle Details -->
                            <div class="col-md-6">
                                <div class="form-floating mb-4">
                                    <input type="text" class="form-control" id="car_brand_model" name="car_brand_model" required>
                                    <label for="car_brand_model" class="form-label">Brand & Model</label>
                                    <div class="invalid-feedback">Please enter the vehicle brand and model</div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating mb-4">
                                    <input type="text" class="form-control" id="reg_number" name="reg_number" required>
                                    <label for="reg_number" class="form-label">License Plate</label>
                                    <div class="invalid-feedback">Please enter a valid license plate</div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-floating mb-4">
                                    <select class="form-select" id="licenceClass" name="licenceClass" data-placeholder=" " style="width: 100%;" required>
                                        <option value=""></option>
                                        @foreach ($licenceClasses as $licenceClass)
                                            <option value="{{ $licenceClass->id }}"
                                                @if(old('licenceClass') == $licenceClass->id) selected @endif>
                                                {{ $licenceClass->class }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <label for="licenceClass" class="fw-semibold">
                                        License type
                                    </label>
                                </div>
                            </div>


                            <div class="col-12">
                                <div class="form-floating mb-4">
                                    <textarea class="form-control" id="car_description" name="car_description" style="height: 100px" placeholder="Vehicle description"></textarea>
                                    <label for="car_description">Description</label>
                                </div>
                            </div>

                            <!-- Image Upload -->
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="fleet_image" class="form-label fw-semibold">Vehicle Image</label>
                                    <input type="file" class="form-control" id="fleet_image" name="fleet_image" accept="image/*">
                                    <div class="form-text">Recommended size: 800x600px</div>
                                    <div class="preview-container mt-2 d-none">
                                        <img id="imagePreview" src="#" alt="Preview" class="img-thumbnail" style="max-height: 150px; display: none;">
                                    </div>
                                </div>
                            </div>

                            <!-- Instructor Selection -->
                            <div class="col-md-6">
                                <div class="form-floating mb-4">
                                    <select class="form-select select2 -instructor" id="instructor" name="instructor" data-placeholder=" " style="width: 100%;">
                                        <option value=""></option>
                                        @foreach ($instructors as $instructor)
                                            <option value="{{ $instructor->id }}"
                                                {{ $instructor->status == 'Suspended' || $instructor->status == 0 ? 'disabled' : '' }}
                                                class="{{ $instructor->status == 'Suspended' || $instructor->status == 0 ? 'text-danger' : '' }}">
                                                {{ $instructor->fname }} {{ $instructor->sname }}
                                                @if ($instructor->status == 'Suspended' || $instructor->status == 0)
                                                    (Unavailable)
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <label for="instructor" class="fw-semibold">
                                        <i class="fa fa-user-tie me-1"></i> Assigned Instructor
                                    </label>
                                </div>
                                <div class="form-text text-muted ps-3">Leave blank if no instructor assigned</div>
                            </div>
                        </div>

                        <!-- Form Footer -->
                        <div class="block-content block-content-full bg-light rounded-bottom text-end">
                            <button type="submit" class="btn btn-primary  rounded-pill px-4">
                                <i class="fa fa-save me-1"></i> Save
                            </button>
                            <button type="button" class="btn btn-alt-secondary me-2  rounded-pill px-4" data-bs-dismiss="modal">
                                <i class="fa fa-times me-1"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>