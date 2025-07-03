<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Course extends Model
{
    use Notifiable, HasUuids, HasFactory, LogsActivity;
    protected $keyType = 'string';
    public $incrementing = false;

    public function Student()
    {
       return $this->hasMany(Student::class);
    }

    public function Invoice()
    {
       return $this->hasMany(Invoice::class);
    }

    public function User()
    {
       return $this->belongsToMany(User::class);
    }

    public function Instructor()
    {
       return $this->belongsTo(Instructor::class);
    }

    public function Attendance()
    {
       return $this->hasMany(Attendance::class);
    }

    public function licenceClass()
    {
        return $this->belongsTo(LicenceClass::class, 'licence_class_id');
    }

    public function Lessons()
    {
        return $this->belongsToMany(Lesson::class, 'course_lesson')
                ->withPivot('lesson_quantity', 'order')
                ->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Course {$this->name} " .
                "{$eventName}."
            );
    }
}
