@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Add course</h1>
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
                    <form action="{{ url('/storecourse') }}" method="POST">
                        @csrf
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" id="course_name" name="course_name">
                            <label for="example-ltf-email">Course name</label>
                        </div>
                        <div class="form-floating mb-4">
                            <textarea type="text" class="form-control" id="course_description" name="course_description" style="height: 150px"></textarea>
                            <label for="example-ltf-password">Description</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="number" class="form-control" id="course_price" name="course_price" value="180000">
                            <label for="example-ltf-password">Price</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="number" class="form-control" id="course_theory" name="course_theory">
                            <label for="example-ltf-password">Number of days for theory</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="number" class="form-control" id="course_practicals" name="course_practicals">
                            <label for="example-ltf-password">Number of days for practicals</label>
                        </div>
                        <div class="form-group mb-4">                            
                            <label for="example-ltf-password">Course Image</label>
                            <input type="file" class="form-control" id="example-ltf-password" name="example-ltf-password" value="">
                        </div>
                        <div class="form-floating mb-4">
                            <select class="form-select" id="instructor" name="instructor">
                                @foreach($instructor as $instructor)
                                    <option selected value="1">{{$instructor->fname}} {{$instructor->fname}}</option>
                                @endforeach
                            </select>
                            <label for="example-ltf-password">Instructor</label>
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
