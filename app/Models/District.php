<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    public function Student()
    {
       return $this->hasMany(Student::class);
    }

    public function Setting()
    {
       return $this->hasMany(Setting::class);
    }
}
