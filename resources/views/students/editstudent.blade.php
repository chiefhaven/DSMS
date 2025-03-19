@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Edit Student; {{$student->fname}} {{$student->sname}}</h1>
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
              <form action="{{ url('/student-update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="text" name="student_id" id="student_id" value="{{$student->id}}" hidden>
                <h2 class="content-heading pt-0">
                  <i class="fa fa-fw fa-user-graduate text-muted me-1"></i> Student Information
                </h2>
                <div class="row push">
                  <div class="col-lg-4">
                    <p class="text-muted">
                      Student details
                    </p>
                  </div>
                  <div class="col-lg-8 col-xl-5">
                    <div class="row">
                      <div class="col-4 form-floating mb-4">
                        <input type="text" class="form-control @error('First name') is-invalid @enderror" id="fname" name="fname" placeholder="haven" value="{{$student->fname}}">
                        <label class="form-label" for="fname">First name</label>
                      </div>
                      <div class="col-4 form-floating mb-4">
                        <input type="text" class="form-control @error('mname') is-invalid @enderror" id="mname" name="mname" placeholder="chikumba" value="{{$student->mname}}">
                        <label class="form-label" for="sname">Other names</label>
                      </div>
                      <div class="col-4 form-floating mb-4">
                        <input type="text" class="form-control @error('sname') is-invalid @enderror" id="sname" name="sname" placeholder="chikumba" value="{{$student->sname}}" required>
                        <label class="form-label" for="sname">Sir name</label>
                      </div>
                    </div>
                    <div class="mb-4 form-floating">
                      <input type="file" name="signature" class="form-control @error('signature') is-invalid @enderror" accept=".jpg,.jpeg,.png"> {{$student->signature}}
                      <label class="form-label" for="signature">Student Signature</label>
                    </div>
                    <div class="col-4 form-floating mb-4">
                        <select class="form-select" id="gender" name="gender">
                          <option value="{{$student->gender}}" selected>{{$student->gender}}</option>
                          <option value="female">Female</option>
                          <option value="female">Male</option>
                          <option value="other">Other</option>
                        </select>
                        <label for="district">Gender</label>
                    </div>
                    <div class="mb-4 form-floating">
                      <input type="text" class="form-control" id="trn" name="trn" placeholder="TRN" value="{{$student->trn}}">
                      <label class="form-label" for="trn">TRN</label>
                    </div>
                    <div class="mb-4 form-floating">
                      <input type="text" class="form-control" id="phone" name="phone" placeholder="+265" value="{{$student->phone}}">
                        <label class="form-label" for="phone">Phone</label>
                    </div>
                    <div class="mb-4 form-floating">
                        <input type="email" class="form-control" id="email" name="email" placeholder="john.doe@example.com" value="{{ $student->user->email ?? '' }}">
                        <label class="form-label" for="email">Email address</label>
                    </div>
                    <div class="mb-4 form-floating">
                      <input type="text" class="form-control" id="date_of_birth" name="date_of_birth" placeholder="DDMMYY" value="{{$student->date_of_birth}}">
                        <label class="form-label" for="email">Date of birth</label>
                    </div>
                    <div class="mb-4 form-floating">
                      <input type="text" class="form-control" id="address" name="address" placeholder="Area 49, Dubai, M2" value="{{$student->address}}">
                        <label class="form-label" for="address">Street address</label>
                    </div>
                    <div class="form-floating mb-4">
                      <select class="form-select" id="district" name="district">
                        @foreach ($district as $district)
                           <option value="{{$district->name}}" {{ $district->id == $student->district_id ? 'selected' : '' }}>{{$district->name}}</option>
                        @endforeach
                      </select>
                      <label for="district">Distirct</label>
                    </div>
                  </div>
                </div>
                <div class="row push">
                  <div class="col-lg-8 col-xl-5 offset-lg-4">
                    <div class="mb-4">
                      <button type="submit" class="btn btn-alt-primary">
                        <i class="fa fa-check-circle opacity-50 me-1"></i>Update
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
