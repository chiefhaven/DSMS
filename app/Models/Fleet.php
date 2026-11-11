<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class Fleet extends Model
{
    use HasFactory, LogsActivity;

    public const DEFAULT_INSURER = 'Prime Insurance';

    public const INSURERS = [
        'Prime Insurance',
        'Britam Insurance',
        'CIC General Insurance',
        'General Alliance Insurance',
        'NICO General Insurance',
        'Reunion Insurance',
        'United General Insurance',
    ];

    protected $attributes = [
        'insurance_provider' => self::DEFAULT_INSURER,
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'manufacture_year' => 'integer',
        'engine_capacity_cc' => 'integer',
        'odometer_km' => 'integer',
        'seating_capacity' => 'integer',
        'next_service_km' => 'integer',
        'insurance_expiry' => 'date',
        'roadworthiness_expiry' => 'date',
        'service_date' => 'date',
    ];

    public $timestamps = true;

    public function estimatedFuelLitresPer100Km(): ?float
    {
        if (!$this->engine_capacity_cc || $this->engine_capacity_cc <= 0) {
            return null;
        }

        $factor = [
            'Petrol' => 1.0,
            'Diesel' => 0.85,
            'Hybrid' => 0.7,
            'LPG' => 1.2,
        ][$this->fuel_type] ?? null;

        if ($factor === null) {
            return null;
        }

        // Planning heuristic, not measured consumption or a manufacturer rating.
        return round((4 + 2 * ($this->engine_capacity_cc / 1000)) * $factor, 2);
    }

    public function Instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function Student()
    {
       return $this->hasMany(Student::class);
    }

    public function licenceClass()
    {
       return $this->belongsTo(licenceClass::class);
    }

    public function VehicleTracker()
    {
       return $this->hasMany(VehicleTracker::class);
    }

    public function FleetTracker()
    {
       return $this->hasMany(FleetTracker::class, 'device_id', 'tracker_device_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Vehicle {$this->car_brand_model}, registration number {$this->car_registration_number} " .
                "{$eventName}."
            );
    }
}
