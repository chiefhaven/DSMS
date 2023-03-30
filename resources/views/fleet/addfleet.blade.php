@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Add Fleet</h1>
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
              <form action="{{ url('/storefleet') }}" method="POST" enctype="multipart/form-data" onsubmit="return true;">
                @csrf      
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
                        <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Instructor's first name" required>
                        <label class="form-label" for="example-email-input-floating">First name</label>
                      </div>
                      <div class="col-6 form-floating mb-4">
                        <input type="text" class="form-control" id="sir_name" name="sir_name" placeholder="Sirname" required>
                        <label class="form-label" for="example-email-input-floating">Sirname</label>
                      </div>
                    </div>
                    <div class="col-12 form-floating mb-4">
                        <select class="form-select" id="gender" name="gender" required>
                          <option value="male">Male</option>
                          <option value="female" selected>Female</option>
                          <option value="other">Other</option>
                        </select>
                        <label for="district">Gender</label>
                    </div>
                    <div class="mb-4 form-floating">
                      <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth" placeholder="DDMMYY" value="27-07-1999" formnovalidate="" required>
                        <label class="form-label" for="email">Date of birth</label>
                    </div>       
                    <div class="mb-4 form-floating">
                      <input type="text" class="form-control" id="phone" name="phone" placeholder="+265" value="+265" required>
                        <label class="form-label" for="example-email-input-floating">Phone</label>
                    </div>
                    <div class="mb-4 form-floating">
                      <input type="text" class="form-control" id="email_address" name="email" placeholder="Instructor's email address" required>
                        <label class="form-label" for="example-email-input-floating">Email address</label>
                    </div>
                    <div class="mb-4 form-floating">
                      <input type="text" class="form-control" id="address" name="address" placeholder="Address">
                        <label class="form-label" for="example-email-input-floating">Street address</label>
                    </div>
                  </div>
                  <div class=" content-heading"><p>&nbsp;</p></div>
                  <div class="col-lg-4">
                    <p class="text-muted">
                      Login details
                    </p>
                  </div>
                  <div class="col-lg-8 col-xl-5">
                      <div class="form-floating mb-4">
                        <input type="text" class="form-control" id="username" name="username" placeholder="Administrator's username">
                        <label class="form-label" for="example-email-input-floating">Username</label>
                      </div>
                      <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="password" name="password" placeholder="password" required>
                        <label class="form-label" for="example-email-input-floating">Password</label>
                      </div>
                  </div>
                </div>
                <div class="row push">
                  <div class="col-lg-8 col-xl-5 offset-lg-4">
                    <div class="mb-4">
                      <button type="submit" class="btn btn-alt-primary">
                        <i class="fa fa-check-circle opacity-50 me-1"></i> Add Instructor
                      </button>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>
  </div>
  <!-- END Hero -->
<script type="text/javascript">
      var path = "{{ route('attendance-student-search') }}";
      $('#student').typeahead({
          source:  function (query, process) {
          return $.get(path, { query: query }, function (data) {
                  return process(data);
              });
          }
      });
  </script>
@endsection
