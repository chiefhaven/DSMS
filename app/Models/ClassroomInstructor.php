<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;

class ClassroomInstructor extends Pivot
{
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = Str::uuid()->toString();
            }
        });
    }

    // Optional: Specify the table name if Laravel can't infer it
    protected $table = 'classroom_instructor';

    public function instructors()
    {
       return $this->belongsToMany(Instructor::class);
    }
}
