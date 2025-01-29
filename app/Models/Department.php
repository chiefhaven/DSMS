<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Department extends Model
{
    use HasUuids, HasFactory, LogsActivity;
    protected $keyType = 'string';
    public $incrementing = false;

    public function instructor()
    {
       return $this->hasOne(Instructor::class);
    }

    public function lesson()
    {
       return $this->hasMany(Lesson::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Department {$this->name} " .
                "{$eventName}."
            );
    }

}
