<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Lesson extends Model
{
    use Notifiable, HasUuids, HasFactory, LogsActivity;
    protected $keyType = 'string';
    public $incrementing = false;

    public function Courses()
    {
        return $this->belongsToMany(Course::class);
    }

    public function Attendance()
    {
        return $this->belongsToMany(Attendance::class);
    }

    public function Instructor()
    {
        return $this->belongsToMany(Instructor::class);
    }

    public function department()
    {
       return $this->belongsTo(Department::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Lesson {$this->name} " .
                "{$eventName}."
            );
    }
}
