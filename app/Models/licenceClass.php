<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LicenceClass extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    public function fleets()
    {
        return $this->hasMany(Fleet::class);
    }

    // Relationship: A licence class has many courses
    public function courses()
    {
        return $this->hasMany(Course::class, 'licence_class_id');
    }

    public function expenseType()
    {
        return $this->hasMany(
            expenseType::class,
            'expense_type_licence_class',
            'licence_class_id',
            'expense_type_id'
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnly(['class', 'description'])
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Licence class '{$this->class}' was {$eventName}."
            );
    }
}
