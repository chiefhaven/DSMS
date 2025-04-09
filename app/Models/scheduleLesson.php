<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ScheduleLesson extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'start_time',
        'finish_time',
        'comments',
        'location',
        'instructor_id',
        'lesson_id',
        'student_id',
    ];

    protected $dates = ['start_time', 'finish_time'];

    /**
     * Get the student associated with the schedule lesson.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'schedule_lesson_students', 'schedule_id', 'student_id')
                    ->withPivot(['lesson_id', 'location', 'status'])
                    ->withTimestamps();
    }

    /**
     * Get the instructor associated with the schedule lesson.
     */
    public function instructor()
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }

    /**
     * Get the lesson associated with the schedule lesson.
     */
    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('user_activity')
        ->logOnlyDirty()
        ->setDescriptionForEvent(function (string $eventName) {
            $student = $this->student;
            $lesson = optional($this->lesson)->name ?? 'Unknown Lesson';

            $studentName = $student
                ? trim("{$student->fname} {$student->mname} {$student->sname}")
                : 'Unknown Student';

            return "Schedule for {$studentName} for {$lesson} has been {$eventName}.";
        });

    }
}

