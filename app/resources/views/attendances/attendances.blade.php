@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-attendances-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Attendances/Schedules</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <a href="/addattendance" class="btn btn-primary">
                    <i class="fa fa-fw fa-user-plus mr-1"></i> Add attendance
            </a>
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
                <div class="table-responsive">
                  <table class="table table-bordered table-striped table-vcenter">
                      <thead>
                          <tr>
                              <th>Date</th>
                              <th>Student</th>
                              <th style="width: 20%;">Class</th>
                              <th class="text-center" style="width: 100px;">Actions</th>
                          </tr>
                      </thead>
                      <tbody>
                        @foreach ($attendance as $attend)
                          <tr>
                              <td class="font-w600">
                                  {{$attend->attendance_date->format('j F, Y')}}
                              </td>
                              <td>
                                  {{$attend->student->fname}} {{$attend->student->sname}}
                              </td>
                              <td>
                                  {{$attend->lesson->name}}
                              </td>
                              <td class="text-center">
                                <div class="dropdown d-inline-block">
                                  <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span class="d-none d-sm-inline-block">Action</span>
                                  </button>
                                  <div class="dropdown-menu dropdown-menu-end p-0">
                                    <div class="p-2">
                                      <form method="POST" action="/editattendance/{{$attend->id}}">
                                        {{ csrf_field() }}
                                        <button class="dropdown-item" type="submit">Edit</button>
                                      </form>
                                      <form method="POST" action="/deleteattendance/{{$attend->id}}">
                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }} 
                                        <button class="dropdown-item" onclick="return confirm('Are you sure you want to delete attendance?')" type="submit">Delete</button>
                                      </form>
                                    </div>
                                  </div>
                                </div>
                              </td>
                          </tr>
                          @endforeach
                      </tbody>
                  </table>
                  {{ $attendance->links('pagination::bootstrap-4') }}
                </div>
          </div>
      </div>
    </div>
<!-- END Hero -->

@endsection
