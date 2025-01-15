@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Edit course</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb"></nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
    <div class="block block-rounded">
        <div class="block-content">
            <div class="row">
                <div class="col-lg-8 col-xl-5">
                    <form action="{{ url('/updatecourse') }}" method="POST">
                        @method('put')
                        @csrf
                        <input type="text" name="course_id" id="course_id" value="{{$course->id}}" hidden>

                        <div class="form-floating mb-4">
                            <input type="text" class="form-control @error('course_name') is-invalid @enderror" id="course_name" name="course_name" placeholder="Course name" value="{{$course->name}}" required>
                            <label for="course_name">Course name</label>
                        </div>
                        <div class="form-floating mb-4">
                            <select type="text" class="form-control @error('course_code') is-invalid @enderror" id="course_code" name="course_code" placeholder="Course code" value="{{$course->class}}" required>
                                <option {{ $course->class == 'B' ? 'selected' : '' }}>B</option>
                                <option {{ $course->class == 'C1' ? 'selected' : '' }}>C1</option>
                            </select>
                            <label for="course_code">Course Class</label>
                        </div>
                        <div class="form-floating mb-4">
                            <textarea type="text" class="form-control @error('course_description') is-invalid @enderror" id="course_description" name="course_description" style="height: 100px" placeholder="Course description" required>
                                {{$course->short_description}}
                            </textarea>
                            <label for="course_description">Description</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="number" class="form-control @error('course_price') is-invalid @enderror" id="course_price" name="course_price" placeholder="Course price" value="{{$course->price}}" required>
                            <label for="course_price">Price</label>
                        </div>
                        {{--  <div class="form-floating mb-4">
                            <input type="number" class="form-control @error('course_theory') is-invalid @enderror" id="course_theory" name="course_theory" placeholder="Theory days" value="{{$course->theory}}">
                            <label for="course_theory">Number of days for theory</label>
                        </div>
                        <div class="form-floating mb-4">
                            <input type="number" class="form-control @error('course_practicals') is-invalid @enderror" id="course_practicals" name="course_practicals" placeholder="Practicals number" value="{{$course->practicals}}">
                            <label for="course_practicals">Number of days for practicals</label>
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
