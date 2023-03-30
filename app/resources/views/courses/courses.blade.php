@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Courses</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <a href="/addcourse" class="btn btn-primary">
                    <i class="fa fa-fw fa-user-plus mr-1"></i> Add Course
            </a>
          </ol>
        </nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
    @if(Session::has('message'))
      <div class="alert alert-info">
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
      <div class="block-content">
      <div class="row">
        @foreach ($course as $course)
        <div class="col-md-6 col-xl-3">
            <div class="block block-rounded block-link-shadow text-center">
                <div class="block-content block-content-full">
                    <img class="img-avatar" src="media/avatars/avatar6.jpg" alt="">
                </div>
                <div class="block-content block-content-full block-content-sm bg-body-light">
                    <p class="font-w600 mb-0">{{$course->name}}</p>
                    <p class="font-size-sm font-italic text-muted mb-0">
                        {{$course->short_description}}
                    </p>
                    <p class="font-size-sm font-italic text-muted mb-0">
                        {{$course->practicals}} days practicals plus {{$course->theory}} days theory. 
                    </p>
                </div>
                <div class="block-content block-content-full">
                    <div class="row gutters-tiny">
                        <div class="col-6">
                            <p class="mb-2">
                                <i class="fa fa-fw fa-users text-black"></i>
                            </p>
                            <p class="font-size-sm text-muted mb-0">
                                22k students enrolled
                            </p>
                        </div>
                        <div class="col-6">
                                <div class="dropdown d-inline-block">
                                  <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span class="d-none d-sm-inline-block">Action</span>
                                  </button>
                                  <div class="dropdown-menu dropdown-menu-end p-0">
                                    <div class="p-2">
                                      <a class="dropdown-item" href="{{ url('/view-course', $course->id) }}">
                                        View
                                      </a>
                                      <form method="POST" action="{{ url('/edit-course', $course->id) }}">
                                        {{ csrf_field() }}
                                        <button class="dropdown-item" type="submit">Edit</button>
                                      </form>
                                      <form method="POST" action="{{ url('/delete-course', $course->id) }}">
                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }} 
                                        <button class="dropdown-item" onclick="return confirm('Are you sure?')" type="submit">Delete</button>
                                      </form>
                                    </div>
                                  </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
      </div>

      </div>
    </div>
  <!-- END Hero -->

@endsection
