@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Edit fleet</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb"></nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
        <div class="block-content">
            <div class="row">
                <div class="col-lg-8 col-xl-5">
                    <form class="mb-5" action="{{ url('/editinstructor', $instructor->id) }}" method="post" onsubmit="return false;">
                        <div class="form-group">
                            <label for="example-ltf-email">Course name</label>
                            <input type="text" class="form-control" id="example-ltf-email" name="example-ltf-email" placeholder="Course name...">
                        </div>
                        <div class="form-group">
                            <label for="example-ltf-password">Duration</label>
                            <input type="tel" class="form-control" id="example-ltf-password" name="example-ltf-password" placeholder="Duration...">
                        </div>
                        <div class="form-group">
                            <label for="example-ltf-password">Price</label>
                            <input type="email" class="form-control" id="example-ltf-password" name="example-ltf-password" placeholder="Price...">
                        </div>
                        <div class="form-group">
                            <label for="example-ltf-password">Instructor</label>
                            <input type="email" class="form-control" id="example-ltf-password" name="example-ltf-password" placeholder="Instructor...">
                        </div>
                        <br>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
  </div>
  <!-- END Hero -->

@endsection
