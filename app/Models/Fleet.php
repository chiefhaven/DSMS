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
