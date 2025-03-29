<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScheduleLesson extends Model
{
    use HasFactory, SoftDeletes; // Enables soft deletes

    protected $fillable = [
        'start_time',
        'finish_time',
        'comments',
        'instructor_id',
        'lesson_id',
        'student_id',
    ];

    protected $dates = ['start_time', 'finish_time'];

    /**
     * Get the student associated with the schedule lesson.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
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
}

