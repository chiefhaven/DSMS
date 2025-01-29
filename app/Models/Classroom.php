<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Classroom extends Model
{
    use Notifiable, HasUuids, HasFactory, LogsActivity;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
        'location'
    ];

    public function students()
    {
        return $this->hasMany(Student::class, 'classroom_id', 'id');
    }

    public function instructors()
    {
        return $this->belongsToMany(Instructor::class, 'classroom_instructor')->using(ClassroomInstructor::class);;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Class room <b>{$this->name}</b> in {$this->location}" .
                "{$eventName}."
            );
    }

}
