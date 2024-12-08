<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Instructor extends Model
{
    use Notifiable, HasUuids, HasFactory;
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

    public function department()
    {
       return $this->belongsTo(Department::class);
    }
}
