<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $casts = ['attendance_date'=>'datetime'];

    public function Student()
    {
        return $this->belongsTo(Student::class);
    }

    public function Course()
    {
        return $this->belongsTo(Course::class);
    }

    public function Lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function Instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function scopeStudent($query, $student)
    {
        if ($student) {
           return $query->whereNotNull('course_id');
        }
        return $query;
    }
}
