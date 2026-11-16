<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Designation extends Model
{
    use HasUuids, HasFactory, LogsActivity, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['name', 'description', 'basic_salary'];

    protected $casts = [
        'basic_salary' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Designation {$this->name} " .
                "{$eventName}."
            );
    }
}
