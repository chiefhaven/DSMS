<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Student extends Model
{
    use HasUuids, HasFactory, LogsActivity, SoftDeletes;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['_token', 'fname', 'sname', 'trn', 'date_of_birth', 'phone', 'gender', 'address', 'district', 'name', 'text'];

    public function getFormattedStatusAttribute()
    {
        return $this->status ? Str::title($this->status) : '-';
    }

    public function Account()
    {
       return $this->hasOne(Account::class);
    }

    public function Enrollment()
    {
       return $this->hasOne(Enrollment::class);
    }

    public function User()
    {
       return $this->hasOne(User::class);
    }

    public function Fleet()
    {
       return $this->belongsTo(Fleet::class);
    }

    public function Course()
    {
        return $this->belongsTo(Course::class);
    }

    public function Invoice()
    {
       return $this->hasOne(Invoice::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function District()
    {
       return $this->belongsTo(District::class);
    }

    public function Payment()
    {
       return $this->hasMany(Payment::class);
    }

    public function expenses()
    {
        return $this->belongsToMany(Expense::class)->withPivot('expense_type');
    }

    public function scheduleLessons()
    {
        return $this->belongsToMany(ScheduleLesson::class, 'schedule_lesson_students', 'student_id', 'schedule_id')
                    ->withPivot(['lesson_id', 'location', 'status'])
                    ->withTimestamps();
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id', 'id');
    }

    public function trainingLevel()
    {
        return $this->belongsTo(TrainingLevel::class, 'trainingLevel_id');
    }

    //Delete relationships!
    protected static function booted () {
        static::deleting(function(Student $student) {
             $student->attendance()->delete();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Student {$this->fname} " .
                ($this->mname ? "{$this->mname} " : "") .
                "{$this->sname} has been {$eventName}."
            );
    }
}
