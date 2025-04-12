@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Add lesson</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb"></nav>
      </div>
    </div>
  </div>


  <div class="content content-full">
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
            <div class="row">
                <div class="col-lg-8 col-xl-5">
                    <form action="{{ url('/storelesson') }}" method="POST">
                        @csrf
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" id="lesson_name" name="lesson_name" required>
                            <label for="lesson_name">Lesson name</label>
                        </div>
                        <div class="form-floating mb-4">
                            <textarea type="text" class="form-control" id="lesson_description" name="lesson_description" style="height: 150px"></textarea>
                            <label for="lesson_description">Description</label>
                        </div>
                        <div class="form-group mb-4">
                            <label for="lesson_image">Lesson Image</label>
                            <input type="file" class="form-control" id="lesson_image" name="lesson_image" value="">
                        </div>
                        <br>
                        <div class="form-group mb-4">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </div>
  <!-- END Hero -->

@endsection
