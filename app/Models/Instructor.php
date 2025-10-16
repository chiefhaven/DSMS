<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class Instructor extends Model
{
    use Notifiable, HasUuids, HasFactory, LogsActivity, SoftDeletes;
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($instructor) {
            // Delete associated user when the instructor is deleted
            User::where('instructor_id', $instructor->id)->delete();
        });
    }

    public function user()
    {
        return $this->hasOne(User::class, 'instructor_id');
    }

    public function Attendances()
    {
        return $this->hasMany(Attendance::class, 'instructor_id');
    }

    public function Lesson()
    {
        return $this->belongsToMany(Lesson::class);
    }

    public function Fleet()
    {
        return $this->hasOne(Fleet::class);
    }

    public function classrooms()
    {
        return $this->belongsToMany(Classroom::class, 'classroom_instructor', 'instructor_id', 'classroom_id')
            ->using(ClassroomInstructor::class);
    }

    public function schedules()
    {
        return $this->hasMany(ScheduleLesson::class, 'instructor_id');
    }

    public function department()
    {
       return $this->belongsTo(Department::class);
    }

    public function payments()
    {
        return $this->hasMany(InstructorPayment::class);
    }

    public function currentAttendances()
    {
        $lastPayment = $this->payments()->latest('payment_date')->first();
        $startDate = $lastPayment ? $lastPayment->payment_date : $this->created_at;

        return $this->attendances()
            ->whereBetween('created_at', [$startDate, now()]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Instructor {$this->fname} " .
                "{$this->sname} {$eventName}."
            );
    }
}
