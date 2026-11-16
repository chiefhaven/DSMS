<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class HrDepartment extends Model
{
    use HasUuids, HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'hr_departments';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['name', 'description'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "HR department {$this->name} " .
                "{$eventName}."
            );
    }
}
