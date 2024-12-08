<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasUuids, HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;

    public function instructor()
    {
       return $this->hasOne(Instructor::class);
    }

    public function lesson()
    {
       return $this->hasMany(Lesson::class);
    }

}
