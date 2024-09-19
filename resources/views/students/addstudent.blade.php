@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Add Student</h1>
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
              <form action="{{ url('/storestudent') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                <h2 class="content-heading pt-0">
                  <i class="fa fa-fw fa-user-graduate text-muted me-1"></i> Student Information
                </h2>
                <div class="row push">
                  <div class="col-lg-3">
                    <p class="text-muted">
                      Student details
                    </p>
                  </div>
                  <div class="col-lg-8 col-xl-8">
                    <div class="row haven-floating">
                        <div class="col-4 form-floating mb-4">
                            <input type="text" class="form-control @error('fname') is-invalid @enderror" id="fname" name="fname" placeholder="First name" value="{{ old('fname') }}" required>
                            <label class="form-label" for="fname">First name</label>
                        </div>
                        <div class="col-4 form-floating mb-4">
                            <input type="text" class="form-control @error('mname') is-invalid @enderror" id="mname" name="mname" placeholder="Other names" value="{{ old('mname') }}">
                            <label class="form-label" for="mname">Other names</label>
                        </div>
                        <div class="col-4 form-floating mb-4">
                            <input type="text" class="form-control @error('sname') is-invalid @enderror" id="sname" name="sname" placeholder="Surname" value="{{ old('sname') }}" required>
                            <label class="form-label" for="sname">Last name</label>
                        </div>
                    </div>
                    <div class="mb-4 form-floating">
                        <input type="file" name="signature" class="form-control @error('signature') is-invalid @enderror" accept=".jpg,.jpeg,.png">
                        <label class="form-label" for="signature">Student Signature</label>
                    </div>
                    <div class="col-12 form-floating mb-4">
                        <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        <label for="gender">Gender</label>
                    </div>
                    <div class="mb-4 form-floating">
                        <input type="number" class="form-control @error('trn') is-invalid @enderror" id="trn" name="trn" placeholder="TRN" value="{{ old('trn') }}">
                        <label class="form-label" for="trn">TRN</label>
                    </div>
                    <div class="mb-4 form-floating">
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" placeholder="+265" value="{{ old('phone', '+265') }}" required>
                        <label class="form-label" for="phone">Phone</label>
                    </div>
                    <div class="mb-4 form-floating">
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="john.doe@example.com" value="{{ old('email') }}" required>
                        <label class="form-label" for="email">Email address</label>
                    </div>
                    <div class="mb-4 form-floating">
                        <input type="text" class="form-control @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth" placeholder="DDMMYY" value="{{ old('date_of_birth', '1999-08-07') }}" required>
                        <label class="form-label" for="date_of_birth">Date of birth</label>
                    </div>
                    <div class="mb-4 form-floating">
                        <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" placeholder="Address" value="{{ old('address') }}" required>
                        <label class="form-label" for="address">Street address</label>
                    </div>
                    <div class="form-floating mb-4">
                        <select class="form-select @error('district') is-invalid @enderror" id="district" name="district" required>
                            @foreach ($district as $district)
                                <option value="{{ $district->name }}" {{ old('district') == $district->name ? 'selected' : '' }}>{{ $district->name }}</option>
                            @endforeach
                        </select>
                        <label for="district">District</label>
                    </div>
                </div>

                <div class=" content-heading"><p>&nbsp;</p></div>
                <div class="col-lg-3">
                <p class="text-muted">
                    Login details
                </p>
                </div>
                <div class="col-lg-12 col-xl-7">
                    <div class="form-floating mb-4">
                        <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" placeholder="Student's username" value="{{ old('username') }}" autocomplete="off">
                        <label class="form-label" for="username">Username</label>
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-floating mb-4">
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Password" required autocomplete="off">
                        <label class="form-label" for="password">Password</label>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                </div>
                <div class="row push">
                  <div class="col-lg-8 col-xl-5 offset-lg-3">
                    <div class="mb-4">
                      <button type="submit" class="btn btn-primary">
                        <i class="fa fa-check-circle opacity-100 me-1"></i> Save
                      </button>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
  </div>
  <!-- END Hero -->

  <script>
    $(document).ready(function() {
        $(document).on('submit', 'form', function() {
            $('button').attr('disabled', 'disabled');
        });
    });

    // Set the datepicker with current date as default value
    $(document).ready(function() {
        var today = new Date();
        var day = ("0" + today.getDate()).slice(-2); // Get day with leading zero
        var month = ("0" + (today.getMonth() + 1)).slice(-2); // Get month with leading zero (Months are zero-based)
        var year = today.getFullYear();

        // Set the datepicker
        $("#date_of_birth").datepicker({
            format: "dd/mm/yyyy",
            autoclose: true,
            todayHighlight: true
        }).datepicker('setDate', day + '-' + month + '-' + year);
    });
   </script>


@endsection
