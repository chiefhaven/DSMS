@extends('layouts.backend')

@section('css_after')
<style>
  #fleet-table_wrapper label,
  #fleet-table_wrapper input,
  #fleet-table_wrapper select,
  #fleet-table .btn,
  #fleet-table .dropdown-item {
    font-family: inherit;
    font-size: inherit;
  }
  #fleet-table_paginate {
    font-size: 1rem;
  }
  #fleet-table th:first-child,
  #fleet-table td:first-child,
  #fleet-table th:nth-child(3),
  #fleet-table td:nth-child(3) {
    white-space: nowrap;
  }
  #fleet-table .fleet-image {
    width: 64px;
    height: 48px;
    object-fit: contain;
    flex-shrink: 0;
  }
</style>
@endsection

@section('content')
@php
  $operationalFields = [
    'insurance_expiry' => 'Insurance expiry', 'roadworthiness_expiry' => 'Roadworthiness expiry',
    'odometer_km' => 'Odometer (km)', 'service_date' => 'Last service',
    'next_service_km' => 'Next service (km)', 'insurance_provider' => 'Insurer',
    'manufacture_year' => 'Year', 'engine_capacity_cc' => 'Engine (cc)',
    'fuel_type' => 'Fuel', 'transmission' => 'Transmission',
  ];
@endphp
<!-- Hero -->
<div class="bg-body-light">
  <div class="content content-full">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
      <div>
        <h1 class="fs-3 fw-semibold mb-1">Fleet</h1>
        <p class="text-muted mb-0">Vehicles and instructor assignments.</p>
      </div>
          <a href="{{ route('fleet.create') }}" class="btn btn-primary rounded-pill px-4 align-self-start align-self-sm-center">
            <i class="fa fa-plus me-1"></i> Add vehicle
          </a>
    </div>
  </div>
</div>
<!-- END Hero -->

<!-- Main content -->
<div class="content content-full">
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

    <div class="row g-3 mb-4">
      @foreach ([
        ['label' => 'Vehicles', 'value' => $vehicleCount, 'icon' => 'fa-car', 'color' => 'primary'],
        ['label' => 'Instructor assigned', 'value' => $assignedVehicles, 'icon' => 'fa-user-check', 'color' => 'success'],
        ['label' => 'Unassigned', 'value' => $vehicleCount - $assignedVehicles, 'icon' => 'fa-user-clock', 'color' => 'warning'],
      ] as $stat)
        <div class="col-12 col-sm-4">
          <div class="block block-rounded mb-0">
            <div class="block-content block-content-full d-flex align-items-center gap-3">
              <span class="item item-rounded bg-{{ $stat['color'] }}-light text-{{ $stat['color'] }}">
                <i class="fa {{ $stat['icon'] }} fs-4" aria-hidden="true"></i>
              </span>
              <div>
                <div class="fs-3 fw-semibold">{{ $stat['value'] }}</div>
                <div class="text-muted fs-sm">{{ $stat['label'] }}</div>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="block block-rounded">
      <div class="block-header block-header-default">
        <h2 class="block-title" id="fleet-table-title">All vehicles</h2>
        <span class="badge bg-primary-light text-primary">{{ $vehicleCount }}</span>
      </div>
      <div class="block-content block-content-full fs-sm">
        <div class="table-responsive">
          <div id="fleet-table-error" class="alert alert-danger d-none" role="alert"></div>
          <table id="fleet-table" class="table table-striped table-hover align-middle w-100 fs-sm" aria-labelledby="fleet-table-title">
            <thead>
              <tr>
                <th scope="col" class="text-end">Actions</th>
                <th scope="col">Vehicle</th>
                <th scope="col">Registration</th>
                <th scope="col">Instructor</th>
                <th scope="col">Students</th>
                @foreach ($operationalFields as $label)
                  <th scope="col" class="text-nowrap">{{ $label }}</th>
                @endforeach
                <th scope="col" class="text-nowrap">Licence class</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
</div>

<!-- SweetAlert2 Delete Confirm -->
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  $(function () {
    $('#fleet-table').DataTable({
      serverSide: true,
      processing: true,
      ajax: {
        url: @json(route('fleet.index')),
        dataSrc: function (response) {
          if (response.error) {
            $('#fleet-table-error').removeClass('d-none').text('Unable to load fleet. Reload the page to try again.');
          }
          return response.data;
        },
        beforeSend: function () {
          $('#fleet-table-error').addClass('d-none').text('');
        },
        error: function (xhr) {
          $('#fleet-table_processing').hide();
          $('#fleet-table-error').removeClass('d-none').text(
            xhr.status === 401 || xhr.status === 403
              ? 'Session expired or access denied. Reload the page.'
              : 'Unable to load fleet. Reload the page to try again.'
          );
        }
      },
      columns: [
        { data: 'actions', name: 'actions', className: 'text-center', orderable: false, searchable: false },
        { data: 'vehicle', name: 'car_brand_model' },
        { data: 'car_registration_number', name: 'car_registration_number', className: 'text-nowrap' },
        { data: 'instructor_name', name: 'instructor_name' },
        { data: 'active_students_count', name: 'active_students_count', searchable: false },
        @foreach ($operationalFields as $field => $label)
          { data: @json($field), name: @json($field), className: 'text-nowrap' },
        @endforeach
        { data: 'licence_class', name: 'licenceClass.class', className: 'text-nowrap' }
      ],
      pageLength: 10,
      lengthMenu: [10, 25, 50, 100],
      scrollX: true,
      scrollCollapse: true,
      pagingType: 'simple_numbers',
      order: [[1, 'asc']],
      columnDefs: [{ targets: 0, orderable: false, searchable: false }],
      language: {
        emptyTable: 'No vehicles yet',
        zeroRecords: 'No matching vehicles'
      }
    });

    $('#fleet-table').on('click', '.delete-confirm', function () {
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
