<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class Fleet extends Model
{
    use HasFactory;

    public function Instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function Student()
    {
       return $this->hasMany(Student::class);
    }
}
