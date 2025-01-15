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
                <div class="col-lg-12 col-xl-5">
                    <form action="{{ url('/storecourse') }}" method="POST">
                        @csrf
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control @error('course_name') is-invalid @enderror" id="course_name" name="course_name" placeholder="Course name">
                            <label for="course_name">Course name</label>
                        </div>
                        <div class="form-floating mb-4">
                            <select type="text" class="form-control @error('course_code') is-invalid @enderror" id="course_code" name="course_code" placeholder="Course class">
                                <option value="null" disabled selected>Select code...</option>
                                <option>B</option>
                                <option>C1</option>
                            </select>
                            <label for="course_code">Course Class</label>
                        </div>
                        <div class="form-floating mb-4">
                            <textarea type="text" class="form-control @error('course_description') is-invalid @enderror" id="course_description" name="course_description" style="height: 100px" placeholder="Course description"></textarea>
                            <label for="course_description">Description</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="number" class="form-control @error('course_price') is-invalid @enderror" id="course_price" name="course_price" placeholder="Course price">
                            <label for="course_price">Price</label>
                        </div>
                        {{--  <div class="form-floating mb-4">
                            <input type="number" class="form-control @error('course_theory') is-invalid @enderror" id="course_theory" name="course_theory" placeholder="Theory days">
                            <label for="course_theory">Number of days for theory</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="number" class="form-control @error('course_practicals') is-invalid @enderror" id="course_practicals" name="course_practicals" placeholder="Practicals number">
                            <label for="course_practicals">Number of days for practicals</label>
                        </div>  --}}
                        <div class="form-group mb-4">
                            <label for="course_image">Course Image</label>
                            <input type="file" class="form-control" id="course_image" name="course_image" placeholder="Course image">
                        </div>
                        {{--  <div class="form-floating mb-4">
                            <select class="form-select @error('instructor') is-invalid @enderror" id="instructor" name="instructor" placeholder="Select instructor">
                                @foreach($instructor as $instructor)
                                    <option selected value="1">{{$instructor->fname}} {{$instructor->fname}}</option>
                                @endforeach
                            </select>
                            <label for="instructor">Instructor</label>
                        </div>  --}}
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
