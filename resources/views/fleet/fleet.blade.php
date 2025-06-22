@extends('layouts.backend')

@section('content')
<!-- Hero -->
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
      <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Fleet Management</h1>
      <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
        <ol class="breadcrumb">
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-block-addfleet">
            <i class="fa fa-plus me-1"></i> Add New Vehicle
          </button>
        </ol>
      </nav>
    </div>
  </div>
</div>
<!-- END Hero -->

<!-- Main content -->
<div class="content content-full">
  <div class="block-content">
    @if(Session::has('message'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ Session::get('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="row g-4">
      @forelse ($fleet as $car)
        <div class="col-md-6 col-xl-4">
          <div class="block block-rounded block-link-shadow h-100">
            <div class="block-content p-0 overflow-hidden">
              <img class="img-fluid" src="{{ asset('public/media/fleet/' . $car->fleet_image) }}" alt="{{ $car->car_brand_model }}">
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
                  <button type="button" class="btn btn-sm btn-alt-secondary dropdown-toggle" id="dropdown-fleet-{{ $car->id }}" data-bs-toggle="dropdown">
                    <i class="fa fa-cog"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end">
                    <form method="GET" action="{{ url('/editfleet', $car->id) }}">
                      @csrf
                      <button class="dropdown-item d-flex align-items-center" type="submit">
                        <i class="fa fa-edit me-2"></i> Edit
                      </button>
                    </form>
                    <button class="dropdown-item d-flex align-items-center text-danger delete-confirm" type="button"
                      data-car="{{ $car->car_brand_model }} - {{ $car->car_registration_number }}"
                      data-url="{{ url('/deletefleet', $car->id) }}">
                      <i class="fa fa-trash me-2"></i> Delete
                    </button>
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

<!-- SweetAlert2 Delete Confirm -->
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.querySelectorAll('.delete-confirm').forEach(button => {
    button.addEventListener('click', function () {
      const carName = this.getAttribute('data-car');
      const url = this.getAttribute('data-url');

      Swal.fire({
        title: `Delete ${carName}?`,
        text: "This vehicle will be permanently removed. Any assigned students will need reassignment.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it!",
        cancelButtonText: "Cancel",
        confirmButtonColor: "#dc3545",
        cancelButtonColor: "#6c757d",
      }).then(result => {
        if (result.isConfirmed) {
          // Create a temporary form to send DELETE request
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = url;

          const csrf = document.createElement('input');
          csrf.type = 'hidden';
          csrf.name = '_token';
          csrf.value = '{{ csrf_token() }}';
          form.appendChild(csrf);

          const method = document.createElement('input');
          method.type = 'hidden';
          method.name = '_method';
          method.value = 'DELETE';
          form.appendChild(method);

          document.body.appendChild(form);
          form.submit();
        }
      });
    });
  });
</script>
@endpush
@endsection
