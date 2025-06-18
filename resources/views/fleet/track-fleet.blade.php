@extends('layouts.backend')

@section('content')
<!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Vehicle location</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <div class="dropdown d-inline-block">

            </div>
          </ol>
        </nav>
      </div>
    </div>
  </div>

<div class="content content-full">
    <div class="block block-rounded">
        <div id="vehicleLocation" style="height: 500px;"></div>
    </div>
</div>

@endsection
