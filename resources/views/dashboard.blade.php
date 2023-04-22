@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Dashboard</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item">App</li>
            <li class="breadcrumb-item active" aria-current="page">dashboard</li>
          </ol>
        </nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
    <div class="block-content">
      @role('superAdmin')
        @include('dashboard_partials.super_admin')
      @endcan
      @role('admin')
        @include('dashboard_partials.admin')
      @endcan
      @role('instructor')
        @include('dashboard_partials.instructor')
      @endcan
      @role('student')
        @include('dashboard_partials.student')
      @endcan
    </div>
  </div>
  <!-- END Hero -->

@endsection
