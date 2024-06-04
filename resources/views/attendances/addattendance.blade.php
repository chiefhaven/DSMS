@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-attendances-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Add Attendance</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
                <div class="flex-grow-1 fs-3 my-2 my-sm-3"  id="time" name="date" value="{{ $date }}" disabled></div>
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
    <div class="block block-rounded block-bordered p-2">
          <div class="block-content">
          <div class="row">
            <p class="text-center">
                Adding attendance for
                <h2 class="text-center">{{$student->fname}} {{$student->mname}} {{$student->sname}}</h2>
            </p>
          <form class="mb-5" action="{{ url('/storeattendance') }}" method="post" onsubmit="return true;">
            @csrf
            <input class="" name="student" value="{{$student->fname}} {{$student->mname}} {{$student->sname}}" hidden>
            <div class="form-floating mb-4">
              <select class="form-select" id="lesson" name="lesson">
                @foreach ($lesson as $lesson)
                   <option value="{{$lesson->name}}">{{$lesson->name}}</option>
                @endforeach
              </select>
              <label for="lesson">Lesson attended</label>
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

<div id="time"></div>

<script type="text/javascript">
  function showTime() {
    var date = new Date(),
        utc = new Date(Date.UTC(
          date.getFullYear(),
          date.getMonth(),
          date.getDate(),
          date.getUTCHours(),
          date.getMinutes(),
          date.getSeconds()
        ));

    document.getElementById('time').innerHTML = utc.toLocaleString();
  }

  setInterval(showTime, 1000);
</script>

@endsection
