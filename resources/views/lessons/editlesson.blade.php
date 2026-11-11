@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3 text-white">Edit lesson</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb"></nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
    <div class="block block-rounded">
        <div class="block-content">
            <div class="row">
                <div class="col-lg-8 col-xl-5">
                    <form action="{{ route('lessons.update', $lesson->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="text" name="lesson_id" id="lesson_id" value="{{$lesson->id}}" hidden>
                        <div class="form-floating mb-4">
                            <input type="text" class="form-control" id="lesson_name" name="lesson_name" value="{{$lesson->name}}">
                            <label for="example-ltf-email">Lesson name</label>
                        </div>
                        <div class="form-floating mb-4">
                            <textarea class="form-control" id="lesson_description" name="lesson_description" style="height: 150px" required>{{$lesson->description}}</textarea>
                            <label for="example-ltf-password">Description</label>
                        </div>
                        <div class="form-group mb-4">
                            <label for="lesson_department">Department</label>
                            <select class="form-control" id="lesson_department" name="lesson_department" required>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" @selected($lesson->department_id == $department->id)>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-4">
                            <label for="lesson_contents">Lesson content</label>
                            <textarea class="form-control" id="lesson_contents" name="lesson_contents">{{ $lesson->lesson_contents->body }}</textarea>
                        </div>
                        <x-form-actions :cancel-url="route('lessons.index')" />
                    </form>
                </div>
            </div>
        </div>
    </div>
  </div>
  <!-- END Hero -->

@endsection
