@if ($field === 'vehicle')
    <div class="d-flex align-items-center gap-3">
        <img src="{{ asset('media/fleet/' . ($car->fleet_image ?: 'driving-school-car-default.png')) }}"
             loading="lazy" alt="" class="fleet-image rounded bg-body-light"
             onerror="this.onerror=null; this.src='{{ asset('media/fleet/driving-school-car-default.png') }}'">
        <div>
            <a href="{{ route('fleet.show', $car->id) }}">{{ $car->car_brand_model }}</a>
            @if ($car->car_description)
                <div class="text-muted">{{ Str::limit($car->car_description, 100) }}</div>
            @endif
        </div>
    </div>
@elseif ($field === 'instructor_name')
    @if ($car->instructor)
        {{ $car->instructor->fname }} {{ $car->instructor->sname }}
    @else
        <span class="text-warning">Not assigned</span>
    @endif
@elseif ($field === 'actions')
    <div class="dropdown d-inline-block">
        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 text-nowrap"
                id="dropdown-fleet-{{ $car->id }}" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-angle-down me-1" aria-hidden="true"></i> Actions
        </button>
        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown-fleet-{{ $car->id }}">
            <a class="dropdown-item" href="{{ route('fleet.show', $car->id) }}">
                <i class="fa fa-eye me-2" aria-hidden="true"></i> View vehicle
            </a>
            @can('edit fleet')
            <a class="dropdown-item" href="{{ route('fleet.edit', $car->id) }}">
                <i class="fa fa-edit me-2" aria-hidden="true"></i> Edit
            </a>
            @endcan
            @can('delete fleet')
            <button class="dropdown-item text-danger delete-confirm" type="button"
                    data-car="{{ $car->car_brand_model }} - {{ $car->car_registration_number }}"
                    data-url="{{ route('fleet.destroy', $car->id) }}">
                <i class="fa fa-trash me-2" aria-hidden="true"></i> Delete
            </button>
            @endcan
        </div>
    </div>
@else
    @php
        $value = $car->{$field};
        $expiryWarning = false;
        if ($value instanceof \DateTimeInterface) {
            $expiryWarning = in_array($field, ['insurance_expiry', 'roadworthiness_expiry'], true)
                && $value->format('Y-m-d') <= \Carbon\Carbon::today()->addDays(20)->format('Y-m-d');
            $value = $value->format('d M Y');
        } elseif (in_array($field, ['engine_capacity_cc', 'odometer_km', 'next_service_km'], true) && $value !== null) {
            $value = number_format($value);
        }
    @endphp
    <span @if ($expiryWarning) class="text-danger fw-semibold" title="Expired or due within 20 days" @endif>{{ $value === null || $value === '' ? '-' : $value }}</span>
@endif
