@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">
            Schedule lessons <span class="badge bg-danger ms-2">New</span>
        </h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb"></nav>
      </div>
    </div>
  </div>

<div class="content content-full">
    @include('components.alert')

    <div class="block block-rounded">
        <div class="p-4">
            <p>Schedule lessons</p>
            Coming soon!
        </div>
    </div>

</div>
  <!-- END Hero -->


@endsection
