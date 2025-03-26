@extends('layouts.backend')

@section('content')
<!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">{{$instructor->fname}} {{$instructor->sname}}</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <div class="dropdown d-inline-block">
              <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="d-sm-inline-block">Action</span>
              </button>
              <div class="dropdown-menu dropdown-menu-end p-0">
                <div class="p-2">
                    <form method="GET" action="{{ url('/editinstructor', $instructor->id) }}">
                        {{ csrf_field() }}
                        <button class="dropdown-item" type="submit">Edit</button>
                    </form>
                    <form method="POST" action="{{ url('/deleteinstructor', $instructor->id) }}">
                        {{ csrf_field() }}
                        {{ method_field('DELETE') }}
                        <button class="dropdown-item delete-confirm" type="submit">Delete</button>
                    </form>
                </div>
              </div>
            </div>
          </ol>
        </nav>
      </div>
    </div>
  </div>

    <div class="content content-full">
        <div class="block block-rounded">
            <ul class="nav nav-tabs nav-tabs-block" role="tablist">
                <li class="nav-item">
                <button class="nav-link active" id="instructor-details-tab" data-bs-toggle="tab" data-bs-target="#instructor-details" role="tab" aria-controls="instructor-details" aria-selected="true">
                    Instructor details
                </button>
                </li>
            </ul>
            <div class="block-content pb-4">
                <div class="tab-pane fade active show" id="instructor-details" role="tabpanel" aria-labelledby="instructor-details-tab">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="py-6 px-4"  style="background: #ffffff; border-radius: 0px; border: thin solid #cdcdcd;">
                                <img class="img-avatar img-avatar96 img-avatar-thumb" src="/../media/avatars/avatar2.jpg" alt="">
                                <h1 class="my-2">{{$instructor->fname}} {{$instructor->sname}}</h1>
                                <p>
                                    Address: {{$instructor->address}} <br>Phone: {{$instructor->phone}}<br>Email: {{$instructor->user->email}}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="py-6 px-2"  style="background: #ffffff; border-radius: 0px; border: thin solid #cdcdcd;">
                                <table class="table">
                                    <thead>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><b>Assigned:</b></td>
                                            <td>
                                                @if($instructor->fleet)
                                                    <p>
                                                        <b>{{ $instructor->fleet->car_registration_number}}</b><br>
                                                        {{ $instructor->fleet->car_brand_model}}<br>
                                                    </p>
                                                @endif
                                                @if($instructor->classrooms)
                                                    <p>
                                                        @foreach ($instructor->classrooms as $classroom)
                                                            <b>{{ $classroom->name }}</b><br>
                                                            {{ $classroom->location }}
                                                        @endforeach
                                                    </p>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><b>Active Students:</b></td>
                                            <td>
                                                {{ ($instructor->fleet && $instructor->fleet->student) || $instructor->classrooms ?
                                                    ($instructor->fleet && $instructor->fleet->student ? $instructor->fleet->student->where('status', '!=', 'Finished')->count() : 0) +
                                                    ($instructor->classrooms ? $instructor->classrooms->sum(function($classroom) {
                                                        return $classroom->students ? $classroom->students->where('status', '!=', 'Finished')->count() : 0;
                                                    }) : 0) : 'None' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><b>Todays attendances:</b></td>
                                            <td>{{ $instructor->attendances->count() ?? '0' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="block block-rounded p-4">
                    Assigned students
                    <h6>Coming soon....</h6>
                </div>
            </div>

            <div class="col-md-6">
                <div class="block block-rounded p-4">
                    Latest attendances
                    <h6>Coming soon....</h6>
                </div>
            </div>
        </div>
    </div>


@endsection
