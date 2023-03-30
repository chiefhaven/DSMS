@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-attendances-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Add Attendance</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            
          </ol>
        </nav>
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
    <div class="block block-rounded block-bordered">
          <div class="block-content">
          <div class="row">
          <form class="mb-5" action="{{ url('/storeattendance') }}" method="post" onsubmit="return true;">
            @csrf
            <div class="form-floating mb-4">
              <input type="datetime-local" class="date form-control" id="date" name="date">
              <label class="form-label" for="example-school-name-input-floating">Date</label>
            </div>
            <div class="form-floating mb-4">
              <input class="form-control" id="student" name="student" type="text" value="">
              <label for="student">Student</label>
            </div>            
            <div class="form-floating mb-4">
              <select class="form-select" id="lesson" name="lesson">
                @foreach ($lesson as $lesson)
                   <option value="{{$lesson->name}}">{{$lesson->name}}</option>
                @endforeach
              </select>
              <label for="lesson">Lesson attended</label>
            </div>
            <div class="form-floating mb-4">
              <select class="form-select" id="instructor" name="instructor">
                @foreach ($instructor as $instructor)
                   <option value="{{$instructor->fname}} {{$instructor->sname}}">{{$instructor->fname}} {{$instructor->sname}}</option>
                @endforeach
              </select>
              <label for="lesson">Instructor Assigned</label>
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
