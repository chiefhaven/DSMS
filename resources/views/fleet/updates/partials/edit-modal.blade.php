@php
    $modalId = 'edit-vehicle-modal-'.$fleet->getKey();
    $errorStep = $errors->has('odometer_km') || $errors->has('odometer_picture') ? 0
        : ($errors->has('service_date') || $errors->has('service_date_picture') ? 1
        : ($errors->has('roadworthiness_expiry') || $errors->has('roadworthiness_picture') ? 2
        : ($errors->has('insurance_expiry') || $errors->has('insurance_picture') ? 3 : 0)));
    $openModalOnLoad = ($errors->any() && (string) old('fleet_id') === (string) $fleet->getKey())
        || (string) request('edit_vehicle') === (string) $fleet->getKey();
@endphp

<div class="modal fade"
     id="{{ $modalId }}"
     tabindex="-1"
     aria-labelledby="{{ $modalId }}-title"
     aria-hidden="true"
     data-fleet-update-modal
     data-open-on-load="{{ $openModalOnLoad ? 'true' : 'false' }}"
     data-error-step="{{ $errorStep }}">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="block block-rounded block-themed block-transparent mb-0">
                <div class="block-header bg-primary">
                    <h3 class="block-title text-white" id="{{ $modalId }}-title">Edit vehicle details</h3>
                    <div class="block-options">
                        <button type="button" class="btn-block-option text-white" data-bs-dismiss="modal" aria-label="Close">
                            <i class="fa fa-times" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <form method="POST" action="{{ route('my-vehicle.store', $fleet) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="fleet_id" value="{{ $fleet->getKey() }}">
                    <div class="block-content py-3 px-4">
                        <div class="fs-sm text-muted mb-3">{{ $fleet->car_brand_model }} · {{ $fleet->car_registration_number }}</div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold" data-fleet-step-title>Step 1 of 4</span>
                            <span class="text-muted fs-sm">Supporting photos are optional</span>
                        </div>
                        <div class="progress mb-4" role="progressbar" aria-label="Vehicle update progress" aria-valuemin="1" aria-valuemax="4" aria-valuenow="1" style="height: 5px;">
                            <div class="progress-bar" data-fleet-progress style="width: 25%;"></div>
                        </div>

                        @if ($errors->has('fleet'))
                            <div class="alert alert-danger" role="alert">{{ $errors->first('fleet') }}</div>
                        @endif

                        <div data-fleet-step="0">
                            <h3 class="h5 mb-1">Current odometer</h3>
                            <p class="text-muted fs-sm mb-3">Enter the vehicle's current odometer reading.</p>
                            <label class="form-label" for="odometer-{{ $fleet->getKey() }}">Current odometer (km)</label>
                            <input class="form-control @error('odometer_km') is-invalid @enderror"
                                   id="odometer-{{ $fleet->getKey() }}" name="odometer_km" type="number" min="0" step="1"
                                   value="{{ old('odometer_km', $fleet->odometer_km) }}" data-step-required required>
                            @error('odometer_km')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="mt-3">
                                <label class="form-label" for="odometer-picture-{{ $fleet->getKey() }}">Odometer photo</label>
                                <input class="form-control @error('odometer_picture') is-invalid @enderror"
                                       id="odometer-picture-{{ $fleet->getKey() }}" name="odometer_picture" type="file"
                                       accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                                <div class="form-text">Optional JPG or PNG, maximum 2 MB.</div>
                                @error('odometer_picture')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div data-fleet-step="1">
                            <h3 class="h5 mb-1">Last service date</h3>
                            <p class="text-muted fs-sm mb-3">Enter the date of the vehicle's most recent service.</p>
                            <label class="form-label" for="service-date-{{ $fleet->getKey() }}">Last service date</label>
                            <input class="form-control @error('service_date') is-invalid @enderror"
                                   id="service-date-{{ $fleet->getKey() }}" name="service_date" type="date"
                                   value="{{ old('service_date', $fleet->service_date?->format('Y-m-d')) }}" data-step-required required>
                            @error('service_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="mt-3">
                                <label class="form-label" for="service-date-picture-{{ $fleet->getKey() }}">Service record photo</label>
                                <input class="form-control @error('service_date_picture') is-invalid @enderror"
                                       id="service-date-picture-{{ $fleet->getKey() }}" name="service_date_picture" type="file"
                                       accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                                <div class="form-text">Optional JPG or PNG, maximum 2 MB.</div>
                                @error('service_date_picture')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div data-fleet-step="2">
                            <h3 class="h5 mb-1">Certificate of Fitness (COF)</h3>
                            <p class="text-muted fs-sm mb-3">Enter the expiry date shown on the vehicle's COF.</p>
                            <label class="form-label" for="cof-due-{{ $fleet->getKey() }}">COF due date</label>
                            <input class="form-control @error('roadworthiness_expiry') is-invalid @enderror"
                                   id="cof-due-{{ $fleet->getKey() }}" name="roadworthiness_expiry" type="date"
                                   value="{{ old('roadworthiness_expiry', $fleet->roadworthiness_expiry?->format('Y-m-d')) }}" data-step-required required>
                            @error('roadworthiness_expiry')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="mt-3">
                                <label class="form-label" for="cof-picture-{{ $fleet->getKey() }}">COF photo</label>
                                <input class="form-control @error('roadworthiness_picture') is-invalid @enderror"
                                       id="cof-picture-{{ $fleet->getKey() }}" name="roadworthiness_picture" type="file"
                                       accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                                <div class="form-text">Optional JPG or PNG, maximum 2 MB.</div>
                                @error('roadworthiness_picture')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div data-fleet-step="3">
                            <h3 class="h5 mb-1">Insurance</h3>
                            <p class="text-muted fs-sm mb-3">Enter the expiry date shown on the vehicle's insurance documents.</p>
                            <label class="form-label" for="insurance-due-{{ $fleet->getKey() }}">Insurance due date</label>
                            <input class="form-control @error('insurance_expiry') is-invalid @enderror"
                                   id="insurance-due-{{ $fleet->getKey() }}" name="insurance_expiry" type="date"
                                   value="{{ old('insurance_expiry', $fleet->insurance_expiry?->format('Y-m-d')) }}" data-step-required required>
                            @error('insurance_expiry')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="mt-3">
                                <label class="form-label" for="insurance-picture-{{ $fleet->getKey() }}">Insurance document photo</label>
                                <input class="form-control @error('insurance_picture') is-invalid @enderror"
                                       id="insurance-picture-{{ $fleet->getKey() }}" name="insurance_picture" type="file"
                                       accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                                <div class="form-text">Optional JPG or PNG, maximum 2 MB.</div>
                                @error('insurance_picture')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="block-content block-content-full bg-body-light d-flex justify-content-between align-items-center">
                        <button class="btn btn-alt-secondary" type="button" data-fleet-step-back disabled>
                            <i class="fa fa-arrow-left me-1" aria-hidden="true"></i>Back
                        </button>
                        <div class="d-flex gap-2">
                            <button class="btn btn-alt-secondary rounded-pill px-4" type="button" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-primary rounded-pill px-4" type="button" data-fleet-step-next>
                                Continue<i class="fa fa-arrow-right ms-1" aria-hidden="true"></i>
                            </button>
                            <button class="btn btn-primary rounded-pill px-4 d-none" type="submit" data-fleet-step-submit>
                                Submit
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.querySelectorAll('[data-fleet-update-modal]').forEach((modal) => {
            const form = modal.querySelector('form');
            const steps = Array.from(modal.querySelectorAll('[data-fleet-step]'));
            const backButton = modal.querySelector('[data-fleet-step-back]');
            const nextButton = modal.querySelector('[data-fleet-step-next]');
            const submitButton = modal.querySelector('[data-fleet-step-submit]');
            const progressBar = modal.querySelector('[data-fleet-progress]');
            const stepTitle = modal.querySelector('[data-fleet-step-title]');
            let currentStep = 0;

            const showStep = (index) => {
                currentStep = Math.max(0, Math.min(index, steps.length - 1));
                steps.forEach((step, stepIndex) => {
                    step.classList.toggle('d-none', stepIndex !== currentStep);
                    const requiredField = step.querySelector('[data-step-required]');
                    if (requiredField) {
                        requiredField.required = stepIndex === currentStep;
                    }
                });

                backButton.disabled = currentStep === 0;
                nextButton.classList.toggle('d-none', currentStep === steps.length - 1);
                submitButton.classList.toggle('d-none', currentStep !== steps.length - 1);
                stepTitle.textContent = `Step ${currentStep + 1} of ${steps.length}`;
                progressBar.style.width = `${((currentStep + 1) / steps.length) * 100}%`;
                progressBar.parentElement.setAttribute('aria-valuenow', String(currentStep + 1));
            };

            backButton.addEventListener('click', () => showStep(currentStep - 1));
            nextButton.addEventListener('click', () => {
                const requiredField = steps[currentStep].querySelector('[data-step-required]');
                if (!requiredField.checkValidity()) {
                    requiredField.reportValidity();
                    return;
                }
                showStep(currentStep + 1);
            });

            form.addEventListener('submit', (event) => {
                for (let stepIndex = 0; stepIndex < steps.length; stepIndex += 1) {
                    const requiredField = steps[stepIndex].querySelector('[data-step-required]');
                    requiredField.required = true;
                    if (!requiredField.checkValidity()) {
                        event.preventDefault();
                        showStep(stepIndex);
                        requiredField.reportValidity();
                        return;
                    }
                }
            });

            modal.addEventListener('hidden.bs.modal', () => showStep(0));
            showStep(0);

            if (modal.dataset.openOnLoad === 'true') {
                const errorStep = Number.parseInt(modal.dataset.errorStep, 10);
                showStep(Number.isNaN(errorStep) ? 0 : errorStep);
                const trigger = document.querySelector(`[data-bs-target="#${modal.id}"]`);
                if (trigger) {
                    trigger.click();
                }
            }
        });
    </script>
@endpush
