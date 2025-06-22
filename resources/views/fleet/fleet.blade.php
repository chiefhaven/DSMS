@extends('layouts.backend')

@section('content')
<!-- Hero -->
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
      <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Fleet Management</h1>
      <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb">
          <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-block-addfleet" href="javascript:void(0)">
            <i class="fa fa-plus me-1"></i> Add New Vehicle
          </a>
        </ol>
      </nav>
    </div>
  </div>
</div>
<!-- END Hero -->

<div class="content content-full">
  <div class="block-content">
    @if(Session::has('message'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ Session::get('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="row g-4">
      @forelse ($fleet as $car)
      <div class="col-md-6 col-xl-4">
        <div class="block block-rounded block-link-shadow h-100">
          <div class="block-content p-0 overflow-hidden">
            <img class="img-fluid" src="{{ asset('public/media/fleet/'.$car->fleet_image) }}" alt="{{ $car->car_brand_model }}">
          </div>
          <div class="block-content block-content-full bg-body-light">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h3 class="fs-lg fw-bold mb-0">{{ $car->car_brand_model }}</h3>
              <span class="badge bg-primary">{{ $car->car_registration_number }}</span>
            </div>
            <p class="text-muted mb-3">{{ Str::limit($car->car_description, 100) }}</p>

            <div class="d-flex justify-content-between small">
              <div>
                <i class="fa fa-user-tie text-muted me-1"></i>
                <span class="text-muted">Instructor: </span>
                @if($car->instructor)
                  <span class="fw-semibold">{{ $car->instructor->fname }} {{ $car->instructor->sname }}</span>
                @else
                  <span class="text-warning">Not assigned</span>
                @endif
              </div>
              <div>
                <i class="fa fa-users text-muted me-1"></i>
                <span class="text-muted">Students: </span>
                <span class="fw-semibold">{{ $car->student()->where('status', '!=', 'Finished')->count() }}</span>
              </div>
            </div>
          </div>
          <div class="block-content block-content-full bg-body-light border-top">
            <div class="d-flex justify-content-between align-items-center">
              <div class="dropdown">
                <button type="button" class="btn btn-sm btn-alt-secondary dropdown-toggle" id="dropdown-fleet-{{ $car->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="fa fa-cog"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-fleet-{{ $car->id }}">
                  <form method="get" action="{{ url('/editfleet', $car->id) }}">
                    @csrf
                    <button class="dropdown-item d-flex align-items-center" type="submit">
                      <i class="fa fa-edit me-2"></i> Edit
                    </button>
                  </form>
                  <form method="POST" action="{{ url('/deletefleet', $car->id) }}" class="delete-fleet-form">
                    @csrf
                    @method('DELETE')
                    <button class="dropdown-item d-flex align-items-center text-danger delete-confirm" type="button" data-car="{{ $car->car_brand_model }} - {{ $car->car_registration_number }}">
                      <i class="fa fa-trash me-2"></i> Delete
                    </button>
                  </form>
                </div>
              </div>
              <a href="/view-fleet/{{ $car->id }}" class="btn btn-sm btn-primary">View Details</a>
            </div>
          </div>
        </div>
      </div>
      @empty
      <div class="col-12">
        <div class="block block-rounded text-center py-6">
          <div class="block-content">
            <i class="fa fa-car fa-3x text-muted mb-3"></i>
            <h4 class="mb-3">No Vehicles in Fleet</h4>
            <p class="text-muted mb-4">Get started by adding your first vehicle to the fleet</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-block-addfleet">
              <i class="fa fa-plus me-1"></i> Add Vehicle
            </button>
          </div>
        </div>
      </div>
      @endforelse
    </div>
  </div>
</div>

@include('fleet.addfleetmodal')

<!-- SweetAlert and Delete Confirmation -->
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.0/sweetalert.min.js"></script>
<script>
  document.querySelectorAll('.delete-confirm').forEach(function(button) {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      var form = this.closest('form');
      var carName = this.getAttribute('data-car');

      swal({
        title: 'Delete ' + carName + '?',
        text: 'This vehicle will be permanently removed. Any assigned students will need reassignment.',
        icon: 'warning',
        buttons: {
          cancel: {
            text: "Cancel",
            visible: true,
            className: "btn btn-alt-secondary"
          },
          confirm: {
            text: "Delete",
            visible: true,
            className: "btn btn-danger"
          }
        },
        dangerMode: true,
      }).then(function (isConfirm) {
        if (isConfirm) {
          form.submit();
        }
      });
    });
  });
</script>
@endpush
@endsection
