<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    public function Course()
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
}
