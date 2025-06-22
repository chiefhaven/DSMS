@extends('layouts.backend')

@section('content')
<!-- Hero -->
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
      <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">{{ $fleet->car_brand_model }}</h1>
      <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb">
          <div class="dropdown d-inline-block">
            <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <span class="d-sm-inline-block">Action</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-0">
              <div class="p-2">
                <!-- Edit Fleet Form -->
                <form method="GET" action="/editfleet/{{ $fleet->id }}">
                  @csrf
                  <button class="dropdown-item" type="submit">Edit Fleet</button>
                </form>
              </div>
            </div>
          </div>
        </ol>
      </nav>
    </div>
  </div>
</div>

<!-- Content -->
<div class="content content-full">
    <div class="block block-rounded block-bordered">
        <div class="block-content">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-4">
                <h2>{{ $fleet->car_brand_model }}</h2>
                <p><strong>Registration Number:</strong> {{ $fleet->car_registration_number }}</p>
                <p><strong>Description:</strong> {{ $fleet->car_description }}</p>

                <img src="{{ asset('public/media/fleet/'.$fleet->fleet_image) }}" alt="{{ $fleet->car_brand_model }}" class="img-fluid rounded">

                <hr>

                <p>
                    <strong>Instructor:</strong>
                    @if ($fleet->instructor)
                    {{ $fleet->instructor->fname }} {{ $fleet->instructor->sname }}
                    @else
                    <span class="text-warning">Not assigned</span>
                    @endif
                </p>

                <p>
                    <strong>Active Students:</strong>
                    {{ $fleet->student()->where('status', '!=', 'Finished')->count() }}
                </p>
            </div>

            <!-- Right Column -->
            <div class="col-md-8">
            <h4>Location and Distance Details</h4>
            <!-- Placeholder content -->
            <p class="text-muted">
                Current location and today distance travelled, estimated fuel consumption
            </p>
            </div>
        </div>
        </div>
    </div>
    <a href="{{ route('fleet.index') }}" class="btn btn-primary rounded-pill px-4">
        <i class="fa fa-arrow-left me-1"></i> Back to Fleet
    </a>
</div>


<!-- SweetAlert Toast -->
@if(Session::has('message'))
  <script>
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: '{{ Session::get('message') }}',
      showConfirmButton: false,
      timer: 3000
    });
  </script>
@endif
@endsection
