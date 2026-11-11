<?php

namespace App\Http\Requests\Concerns;

use App\Models\Fleet;
use Illuminate\Validation\Rule;

trait VehicleDetailsRules
{
    public function vehicleDetailsRules(?Fleet $fleet = null): array
    {
        $insurers = Fleet::INSURERS;
        if ($fleet && $fleet->insurance_provider) {
            $insurers[] = $fleet->insurance_provider;
        }
        return [
            'make' => 'required_without:car_brand_model|string|max:95',
            'model' => 'required_without:car_brand_model|string|max:95',
            'manufacture_year' => 'nullable|integer|min:1886|max:'.(date('Y') + 1),
            'vin' => 'nullable|string|max:50',
            'engine_number' => 'nullable|string|max:50',
            'engine_capacity_cc' => 'required|integer|min:1|max:4294967295',
            'fuel_type' => ['nullable', Rule::in(['Petrol', 'Diesel', 'Electric', 'Hybrid', 'LPG', 'Other'])],
            'transmission' => ['nullable', Rule::in(['Manual', 'Automatic', 'CVT', 'Other'])],
            'colour' => 'nullable|string|max:50',
            'odometer_km' => 'nullable|integer|min:0|max:4294967295',
            'seating_capacity' => 'nullable|integer|min:1|max:200',
            'insurance_provider' => ['nullable', 'string', 'max:255', Rule::in($insurers)],
            'insurance_expiry' => 'nullable|date_format:Y-m-d',
            'roadworthiness_expiry' => 'nullable|date_format:Y-m-d',
            'service_date' => 'nullable|date_format:Y-m-d',
            'next_service_km' => 'nullable|integer|min:0|max:4294967295',
        ];
    }
}
