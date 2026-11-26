<div class="block block-rounded block-bordered mb-4">
    <div class="block-header block-header-default">
        <h2 class="block-title">Specifications &amp; maintenance</h2>
    </div>
    <div class="block-content block-content-full" id="vehicle-specifications">
        <div class="row g-3">
            @foreach ([
                [
                    'title' => 'Vehicle',
                    'fields' => [
                        'make' => 'Make', 'model' => 'Model', 'manufacture_year' => 'Year',
                        'vin' => 'VIN / chassis', 'colour' => 'Colour', 'seating_capacity' => 'Seats',
                    ],
                ],
                [
                    'title' => 'Engine & drive',
                    'fields' => [
                        'engine_capacity_cc' => 'Engine (cc)', 'engine_number' => 'Engine number',
                        'fuel_type' => 'Fuel type', 'transmission' => 'Transmission',
                        'odometer_km' => 'Odometer (km)',
                    ],
                ],
                [
                    'title' => 'Insurance & service',
                    'fields' => [
                        'insurance_provider' => 'Insurer', 'insurance_expiry' => 'Insurance expiry',
                        'roadworthiness_expiry' => 'Roadworthiness expiry', 'service_date' => 'Last service',
                        'next_service_km' => 'Next service (km)',
                    ],
                ],
            ] as $group)
                <div class="col-12 col-lg-4">
                    <section class="bg-body-light rounded-3 p-3 h-100" aria-labelledby="specification-group-{{ $loop->index }}">
                        <h3 class="fs-base fw-semibold mb-3" id="specification-group-{{ $loop->index }}">
                            {{ $group['title'] }}
                        </h3>
                        <dl class="mb-0">
                            @foreach ($group['fields'] as $field => $label)
                                @php
                                    $value = $fleet->{$field};
                                    if ($value instanceof \DateTimeInterface) {
                                        $value = $value->format('d M Y');
                                    } elseif (in_array($field, ['engine_capacity_cc', 'odometer_km', 'next_service_km'], true) && $value !== null) {
                                        $value = number_format($value);
                                    }
                                @endphp
                                <div class="row g-2 py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                                    <dt class="col-6 text-muted fs-sm fw-normal">{{ $label }}</dt>
                                    <dd class="col-6 text-end text-break fs-sm fw-semibold mb-0 {{ $value === null || $value === '' ? 'text-muted' : '' }}">{{ $value === null || $value === '' ? '-' : $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </section>
                </div>
            @endforeach
        </div>
    </div>
</div>
