@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Edit lesson</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb"></nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
    <div class="block block-rounded">
        <div class="block-content">
            <div class="row">
                <div class="col-lg-8 col-xl-5">
                    <form action="{{ url('/updatelesson') }}" method="POST">
                        @csrf
                        <input type="text" name="lesson_id" id="lesson_id" value="{{$lesson->id}}" hidden>
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" id="lesson_name" name="lesson_name" value="{{$lesson->name}}">
                            <label for="example-ltf-email">Lesson name</label>
                        </div>
                        <div class="form-floating mb-4">
                            <textarea type="text" class="form-control" id="lesson_description" name="lesson_description" style="height: 150px">{{$lesson->short_description}}</textarea>
                            <label for="example-ltf-password">Description</label>
                        </div>
                        <div class="form-group mb-4">
                            <label for="example-ltf-password">Lesson Image</label>
                            <input type="file" class="form-control" id="example-ltf-password" name="example-ltf-password" value="course.png">
                        </div>
                        <br>
                        <div class="form-group mb-4">
                            <button type="submit" class="btn btn-primary">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </div>
  <!-- END Hero -->

@endsection
