@extends('layouts.backend')

@section('content')
    @forelse ($fleets as $fleet)
            @php
                $pendingSubmission = $pendingByFleet->get($fleet->id);
            @endphp
            <div class="bg-body-light">
                <div class="content content-full">
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                        <div>
                            <a class="fs-sm text-muted" href="{{ route('my-vehicle') }}">My vehicle</a>
                            <h1 class="fs-3 fw-semibold mt-2 mb-1">{{ $fleet->car_brand_model }}</h1>
                            <p class="text-muted mb-0">{{ $fleet->car_registration_number }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content content-full pt-0">
                @if ($loop->first && session('message'))
                    <div class="alert alert-success" role="status">{{ session('message') }}</div>
                @endif
                @if ($loop->first && $errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="block block-rounded block-bordered h-100 mb-0">
                            <div class="block-header block-header-default">
                                <h2 class="block-title">Vehicle details</h2>
                                <span class="badge bg-primary-light text-primary">{{ $fleet->car_registration_number }}</span>
                            </div>
                            <div class="block-content block-content-full">
                                <div class="mb-4">
                                    <img src="{{ asset('media/fleet/'.($fleet->fleet_image ?: 'driving-school-car-default.png')) }}"
                                         alt="{{ $fleet->car_brand_model }}"
                                         class="img-fluid rounded-3 bg-body-light w-100 p-3"
                                         style="height: 220px; object-fit: contain;"
                                         onerror="this.onerror=null; this.src='{{ asset('media/fleet/driving-school-car-default.png') }}'">
                                </div>
                                @if ($fleet->car_description)
                                    <p class="text-muted fs-sm">{{ $fleet->car_description }}</p>
                                @endif
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between gap-3 px-0">
                                        <span class="text-muted">Licence class</span>
                                        <span class="fw-semibold">{{ $fleet->licenceClass?->class ?? '—' }}</span>
                                    </div>
                                </div>
                                @if ($pendingSubmission)
                                    <div class="alert alert-warning mt-3 mb-0" role="status">
                                        An update submitted {{ $pendingSubmission->created_at->format('Y-m-d H:i') }} is awaiting approval.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        @include('fleet.partials.vehicle-location-map', [
                            'fleet' => $fleet,
                            'locationsUrl' => route('my-vehicle.locations', $fleet),
                        ])
                    </div>
                </div>

                @include('fleet.partials.vehicle-specifications', [
                    'fleet' => $fleet,
                    'currentOdometerKm' => $currentOdometerByFleet[$fleet->getKey()] ?? $fleet->odometer_km,
                ])
            </div>

        @empty
            <div class="bg-body-light">
                <div class="content content-full">
                    <h1 class="fs-3 fw-semibold my-2 my-sm-3">My vehicle</h1>
                </div>
            </div>
            <div class="content content-full pt-0">
                <div class="alert alert-info" role="status">No fleet vehicle is currently assigned to you.</div>
            </div>
        @endforelse

        @if ($submissions->isNotEmpty())
          <div class="content content-full pt-0">
            <div class="block block-rounded block-bordered">
                <div class="block-header block-header-default">
                    <h2 class="block-title">Recent submissions</h2>
                </div>
                <div class="block-content block-content-full fs-sm p-0">
                    <div class="table-responsive">
                    <table class="table table-striped table-hover table-vcenter align-middle mb-0">
                        <thead class="bg-body-light">
                            <tr class="text-uppercase fs-xs text-muted">
                                <th>Vehicle</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Review note</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($submissions as $submission)
                                @php
                                    $statusClass = match ($submission->status) {
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        default => 'warning',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $submission->fleet?->car_brand_model ?? 'Vehicle removed' }} ({{ $submission->fleet?->car_registration_number ?? '—' }})</td>
                                    <td>{{ $submission->created_at->format('Y-m-d H:i') }}</td>
                                    <td><span class="badge bg-{{ $statusClass }}-light text-{{ $statusClass }}">{{ ucfirst($submission->status) }}</span></td>
                                    <td>{{ $submission->review_note ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
          </div>
        @endif
@endsection
