@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Edit Fleet</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb"></nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
          @if(Session::has('message'))
            <div class="alert alert-success">
              {{Session::get('message')}}
            </div>
          @endif

          @if ($errors->any())
              <div class="alert alert-danger">
                  <ul>
                      @foreach ($errors->all() as $error)
                          <li>{{ $error }}</li>
                      @endforeach
                  </ul>
              </div>
          @endif
        <div class="block block-rounded block-bordered">
    <div class="block-content">
        <form class="mb-5" action="{{ route('fleet.update', $fleet->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT') {{-- Important: Use PUT method for updates --}}

            <div class="row">
                <div class="col-4 form-floating mb-4">
                    <input type="text" class="form-control @error('car_brand_model') is-invalid @enderror"
                           id="car_brand_model" name="car_brand_model"
                           value="{{ old('car_brand_model', $fleet->car_brand_model) }}" required>
                    <label class="px-4" for="car_brand_model">Car brand/Model</label>
                    @error('car_brand_model')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-4 form-floating mb-4">
                    <input type="text" class="form-control @error('reg_number') is-invalid @enderror"
                           id="reg_number" name="reg_number"
                           value="{{ old('reg_number', $fleet->car_registration_number) }}" required>
                    <label class="px-4" for="reg_number">Car number plate</label>
                    @error('reg_number')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-4 form-floating mb-4">
                    <input type="text" class="form-control @error('tracker_device_id') is-invalid @enderror"
                           id="tracker_device_id" name="tracker_device_id"
                           value="{{ old('tracker_device_id', $fleet->tracker_unique_id) }}">
                    <label class="px-4" for="tracker_device_id">Tracking device ID</label>
                    @error('tracker_device_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="form-floating mb-4">
                <select class="form-select @error('licenceClass') is-invalid @enderror"
                        id="licenceClass" name="licenceClass" required>
                    <option value="">Select Licence Type</option>
                    @foreach ($licenceClasses as $licenceClass)
                        <option value="{{ $licenceClass->id }}"
                            {{ old('licenceClass', $fleet->licence_class_id) == $licenceClass->id ? 'selected' : '' }}>
                            {{ $licenceClass->class }}
                        </option>
                    @endforeach
                </select>
                <label for="licenceClass">Licence type</label>
                @error('licenceClass')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-floating mb-4">
                <textarea class="form-control @error('car_description') is-invalid @enderror"
                          id="car_description" name="car_description"
                          style="height: 100px">{{ old('car_description', $fleet->car_description) }}</textarea>
                <label>Car description</label>
                @error('car_description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-floating mb-4">
                <input type="file" class="form-control @error('fleet_image') is-invalid @enderror"
                       id="fleet_image" name="fleet_image" accept="image/jpeg,image/png,image/jpg,image/gif">
                <label>Car Image</label>
                @if($fleet->fleet_image)
                    <small class="form-text text-muted d-block mt-1">
                        Current image: {{ $fleet->fleet_image }}
                    </small>
                @endif
                @error('fleet_image')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-floating mb-4">
                <select class="form-select @error('instructor') is-invalid @enderror"
                        id="instructor" name="instructor" required>
                    <option value="">Select Instructor</option>
                    @foreach ($instructors as $instructor)
                        <option value="{{ $instructor->id }}"
                            {{ old('instructor', optional($fleet->instructor)->id) == $instructor->id ? 'selected' : '' }}
                            {{ $instructor->status == 'Suspended' || $instructor->status == 0 ? 'disabled' : '' }}
                            class="{{ $instructor->status == 'Suspended' || $instructor->status == 0 ? 'text-danger' : '' }}">
                            {{ $instructor->fname }} {{ $instructor->sname }}
                            @if ($instructor->status == 'Suspended' || $instructor->status == 0)
                                (Unavailable)
                            @endif
                        </option>
                    @endforeach
                </select>
                <label for="instructor">Assign instructor</label>
                @error('instructor')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-floating mb-4">
                <button type="submit" class="btn btn-primary">Update Fleet</button>
                <a href="{{ route('fleet.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
  </div>
  <!-- END Hero -->

@endsection
