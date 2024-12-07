@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Edit Instructor; {{$instructor->fname}} {{$instructor->sname}}</h1>
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
        <div class="block block-rounded">
            <div class="block-content">
              <form action="{{ url('/updateinstructor') }}" enctype="multipart/form-data" method="POST">
                @csrf
                <input type="text" name="instructor_id" id="instructor_id" value="{{$instructor->id}}" hidden>
                <h2 class="content-heading pt-0">
                  <i class="fa fa-fw fa-user text-muted me-1"></i> Instructor Information
                </h2>
                <div class="row push">
                  <div class="col-lg-4">
                    <p class="text-muted">
                      Instructor details
                    </p>
                  </div>
                  <div class="col-lg-8 col-xl-5">
                    <div class="row">
                      <div class="col-6 form-floating mb-4">
                        <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Instructor's first name" value="{{$instructor->fname}}">
                        <label class="form-label" for="example-email-input-floating">First name</label>
                      </div>
                      <div class="col-6 form-floating mb-4">
                        <input type="text" class="form-control" id="sir_name" name="sir_name" placeholder="Sirname" value="{{$instructor->sname}}">
                        <label class="form-label" for="example-email-input-floating">Sirname</label>
                      </div>
                    </div>
                    <div class="col-4 form-floating mb-4">
                        <select class="form-select" id="gender" name="gender">
                          <option value="{{$instructor->gender}}" selected>{{$instructor->gender}}</option>
                          <option value="female">Female</option>
                          <option value="female">Male</option>
                          <option value="other">Other</option>
                        </select>
                        <label for="district">Gender</label>
                    </div>
                    <div class="mb-4 form-floating">
                      <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth" placeholder="DDMMYY" value="{{$instructor->date_of_birth}}" formnovalidate="">
                        <label class="form-label" for="text">Date of birth</label>
                    </div>
                    <div class="mb-4 form-floating">
                      <input type="text" class="form-control" id="phone" name="phone" placeholder="+265" value="{{$instructor->phone}}">
                        <label class="form-label" for="example-email-input-floating">Phone</label>
                    </div>
                    <div class="mb-4 form-floating">
                      <input type="email" class="form-control" id="email_address" name="email" placeholder="Email address" value="{{$instructor->user->email}}">
                        <label class="form-label" for="example-email-input-floating">Email address</label>
                    </div>
                    <div class="mb-4 form-floating">
                      <input type="text" class="form-control" id="address" name="address" placeholder="Address" value="{{$instructor->address}}">
                        <label class="form-label" for="example-email-input-floating">Street address</label>
                    </div>
                    <div class="form-floating mb-4">
                      <select class="form-select" id="district" name="district">
                        @foreach ($district as $district)
                           <option value="{{$district->name}}" {{ $district->id == $instructor->district_id ? 'selected' : '' }}>{{$district->name}}</option>
                        @endforeach
                      </select>
                      <label for="district">Distirct</label>
                    </div>
                    <div class="form-floating mb-4">
                        <select class="form-select" id="department" name="department">
                            <option value="practical" {{ $instructor->department == 'practical' ? 'selected' : '' }}>Practical</option>
                            <option value="theory" {{ $instructor->department == 'theory' ? 'selected' : '' }}>Theory</option>
                        </select>
                      <label for="department">Department</label>
                    </div>
                  </div>
                </div>
                <div class="row push">
                  <div class="col-lg-8 col-xl-5 offset-lg-4">
                    <div class="mb-4">
                      <button type="submit" class="btn btn-alt-primary">
                        <i class="fa fa-check-circle opacity-50 me-1"></i> Update Instructor
                      </button>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
  </div>
  <!-- END Hero -->

@endsection
