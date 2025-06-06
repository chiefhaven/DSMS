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
            <form class="mb-5" action="{{ url('/updatefleet') }}" method="POST" enctype="multipart/form-data" onsubmit="return true;">
                  @csrf
                  <input type="text" class="form-control" id="id" name="id" value="{{$fleet->id}}" required hidden>
              <div class="row">
                  <div class="col-6 form-floating mb-4">
                      <input type="text" class="form-control" id="car_brand_model" name="car_brand_model" value="{{$fleet->car_brand_model}}" required>
                      <label class="px-4" for="car_brand_model">Car brand/Model</label>
                  </div>
                  <div class="col-6 form-floating mb-4">
                      <input type="text" class="form-control" id="reg_number" name="reg_number" value="{{$fleet->car_registration_number}}" required>
                      <label class="px-4" for="car_registration_number">Car number plate</label>
                  </div>
              </div>
              <div class="col-12 form-floating mb-4">
                  <textarea class="form-control" id="car_description" name="car_description" style="height: 100px">{{$fleet->car_description}}</textarea>
                  <label>Car description</label>
              </div>
              <div class="col-12 form-floating mb-4">
                  <input type="file" class="form-control" id="fleet_image" name="fleet_image">
                  <label>Car Image</label>
              </div>
              <div class="form-floating mb-4">
                <select class="form-select" id="instructor" name="instructor">
                    @foreach ($instructors as $instructor)
                        <option value="{{ $instructor->id }}"
                            {{ $fleet->instructor && $fleet->instructor->id && $instructor->id == $fleet->instructor->id ? 'selected' : '' }}>
                            {{ $instructor->fname }} {{ $instructor->sname }}
                        </option>
                    @endforeach
                </select>
                <label for="lesson">Assign instructor</label>
              </div>
              <div class="form-floating mb-4">
                  <button type="submit" class="btn btn-primary">Update</button>
              </div>
          </form>
          </div>
        </div>
  </div>
  <!-- END Hero -->

@endsection
