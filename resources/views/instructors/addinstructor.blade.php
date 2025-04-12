@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Add Instructor</h1>
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
              <form action="{{ url('/storeinstructor') }}" method="POST" enctype="multipart/form-data" onsubmit="return true;" autocomplete="off">
                @csrf
                @include('instructors/includes/instructorsForm')
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

@endsection
