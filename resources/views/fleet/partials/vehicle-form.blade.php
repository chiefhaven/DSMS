@php
    $sections = [
        'Vehicle details' => [
            'make' => ['label' => 'Make', 'required' => true, 'maxlength' => 95],
            'model' => ['label' => 'Model', 'required' => true, 'maxlength' => 95],
            'reg_number' => ['label' => 'Number plate', 'required' => true, 'attribute' => 'car_registration_number'],
            'manufacture_year' => ['label' => 'Manufacture year', 'type' => 'number', 'min' => 1886, 'max' => date('Y') + 1],
            'vin' => ['label' => 'VIN / chassis number'],
            'engine_number' => ['label' => 'Engine number'],
            'engine_capacity_cc' => ['label' => 'Engine capacity (cc)', 'required' => true, 'type' => 'number', 'min' => 1, 'max' => 4294967295],
            'fuel_type' => ['label' => 'Fuel type', 'options' => ['Petrol', 'Diesel', 'Electric', 'Hybrid', 'LPG', 'Other']],
            'transmission' => ['label' => 'Transmission', 'options' => ['Manual', 'Automatic', 'CVT', 'Other']],
            'colour' => ['label' => 'Colour'],
            'odometer_km' => ['label' => 'Odometer (km)', 'type' => 'number', 'min' => 0, 'max' => 4294967295],
            'seating_capacity' => ['label' => 'Seats', 'type' => 'number', 'min' => 1, 'max' => 200],
        ],
        'Insurance & maintenance' => [
            'insurance_provider' => ['label' => 'Insurer', 'options' => \App\Models\Fleet::INSURERS],
            'insurance_expiry' => ['label' => 'Insurance expiry', 'type' => 'date'],
            'roadworthiness_expiry' => ['label' => 'Roadworthiness expiry', 'type' => 'date'],
            'service_date' => ['label' => 'Last service date', 'type' => 'date'],
            'next_service_km' => ['label' => 'Next service (km)', 'type' => 'number', 'min' => 0, 'max' => 4294967295],
        ],
    ];
@endphp

<div class="bg-body-light">
    <div class="content content-full">
        <a class="text-muted fs-sm" href="{{ route('fleet.index') }}"><i class="fa fa-arrow-left me-1" aria-hidden="true"></i> Back to fleet</a>
        <h1 class="fs-3 fw-semibold mt-2 mb-0">{{ $editing ? 'Editing '.$fleet->car_brand_model : 'Add vehicle' }}</h1>
    </div>
</div>

<div class="content content-full">
    @if (session('message'))
        <div class="alert alert-success" role="status">{{ session('message') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ $editing ? route('fleet.update', $fleet->id) : route('fleet.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif
        @foreach ($sections as $title => $fields)
            <div class="block block-rounded">
                <div class="block-header block-header-default">
                    <h2 class="block-title">{{ $title }}</h2>
                </div>
                <div class="block-content block-content-full">
                    @if ($loop->first)
                        <p class="fs-sm text-muted mb-4"><span class="text-danger">*</span> Required. Other fields are optional.</p>
                        @if ($editing && (!$fleet->make || !$fleet->model))
                            <p class="fs-sm text-muted">Current vehicle: {{ $fleet->car_brand_model }}. Enter make and model separately.</p>
                        @endif
                    @endif
                    <div class="row g-4">
                        @foreach ($fields as $name => $field)
                            @php
                                $value = old($name, $fleet->{$field['attribute'] ?? $name});
                                if (($field['type'] ?? '') === 'date' && $value instanceof \DateTimeInterface) {
                                    $value = $value->format('Y-m-d');
                                }
                            @endphp
                            <div class="col-12 col-md-6 col-xl-4">
                                <label class="form-label" for="{{ $name }}">{{ $field['label'] }} @if ($field['required'] ?? false)<span class="text-danger">*</span>@endif</label>
                                @if (isset($field['options']))
                                    <select class="form-select @error($name) is-invalid @enderror" id="{{ $name }}" name="{{ $name }}" @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
                                        <option value="">Select</option>
                                        @if ($name === 'insurance_provider' && $editing && $fleet->insurance_provider && !in_array($fleet->insurance_provider, $field['options'], true))
                                            <option value="{{ $fleet->insurance_provider }}" @selected($value === $fleet->insurance_provider)>{{ $fleet->insurance_provider }} (existing)</option>
                                        @endif
                                        @foreach ($field['options'] as $option)
                                            <option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="{{ $field['type'] ?? 'text' }}" class="form-control @error($name) is-invalid @enderror" id="{{ $name }}" name="{{ $name }}" value="{{ $value }}" @required($field['required'] ?? false) @if (isset($field['min'])) min="{{ $field['min'] }}" max="{{ $field['max'] }}" step="1" @endif @if (isset($field['maxlength'])) maxlength="{{ $field['maxlength'] }}" @endif @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
                                @endif
                                @error($name)
                                    <div class="invalid-feedback" id="{{ $name }}-error">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <div class="block block-rounded">
            <div class="block-header block-header-default">
                <h2 class="block-title">Assignment &amp; extras</h2>
            </div>
            <div class="block-content block-content-full">
                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label" for="licenceClass">Licence class <span class="text-danger">*</span></label>
                        <select class="form-select @error('licenceClass') is-invalid @enderror" id="licenceClass" name="licenceClass" required>
                            <option value="">Select</option>
                            @foreach ($licenceClasses as $licenceClass)
                                <option value="{{ $licenceClass->id }}" @selected(old('licenceClass', $fleet->licence_class_id) == $licenceClass->id)>{{ $licenceClass->class }}</option>
                            @endforeach
                        </select>
                        @error('licenceClass')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="instructor">Instructor @if (!$editing)<span class="text-danger">*</span>@endif</label>
                        <select class="form-select @error('instructor') is-invalid @enderror" id="instructor" name="instructor" @required(!$editing)>
                            <option value="">{{ $editing ? 'Not assigned' : 'Select' }}</option>
                            @foreach ($instructors as $instructor)
                                <option value="{{ $instructor->id }}" @selected(old('instructor', $fleet->instructor_id) == $instructor->id) @disabled($instructor->status == 'Suspended' || $instructor->status == 0)>
                                    {{ $instructor->fname }} {{ $instructor->sname }}
                                </option>
                            @endforeach
                        </select>
                        @error('instructor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="tracker_device_id">Tracking device ID</label>
                        <input type="text" class="form-control @error('tracker_device_id') is-invalid @enderror" id="tracker_device_id" name="tracker_device_id" value="{{ old('tracker_device_id', $fleet->tracker_unique_id) }}">
                        @error('tracker_device_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="fleet_image">Vehicle image</label>
                        <input type="file" class="form-control @error('fleet_image') is-invalid @enderror" id="fleet_image" name="fleet_image" accept="image/jpeg,image/png,image/gif">
                        <div class="form-text">JPG/PNG/GIF, max 2 MB. @if ($editing)Leave blank to keep existing.@endif</div>
                        @error('fleet_image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="car_description">Description</label>
                        <textarea class="form-control @error('car_description') is-invalid @enderror" id="car_description" name="car_description" rows="3" maxlength="1000">{{ old('car_description', $fleet->car_description) }}</textarea>
                        @error('car_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
        <x-form-actions :cancel-url="route('fleet.index')" />
    </form>
</div>
