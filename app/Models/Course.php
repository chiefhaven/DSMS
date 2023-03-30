<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

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

    public function Lesson()
    {
        return $this->belongsToMany(Lesson::class);
    }
}
