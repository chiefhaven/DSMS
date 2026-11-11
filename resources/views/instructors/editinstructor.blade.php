@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Edit Instructor; {{$instructor->fname}} {{$instructor->sname}}</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb"></nav>
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
        <div class="block block-rounded">
            <div class="block-content">
              <form action="{{ route('instructors.update', $instructor->id) }}" enctype="multipart/form-data" method="POST">
                  @method('PUT')
                @csrf
                <input type="text" name="instructor_id" id="instructor_id" value="{{$instructor->id}}" hidden>
                @include('instructors/includes/instructorsForm')

                <x-form-actions :cancel-url="route('instructors.index')" />
              </form>
            </div>
          </div>
  </div>
  <!-- END Hero -->

@endsection
