<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class Instructor extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($instructor) {
            // Delete associated user when the instructor is deleted
            User::where('instructor_id', $instructor->id)->delete();
        });
    }

    public function User()
    {
       return $this->hasOne(User::class);
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
        return $this->hasMany(Classroom::class, 'classroom_instructor')->using(ClassroomInstructor::class);;
    }
}
