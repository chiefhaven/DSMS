<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsReport extends Model
{
    protected $fillable = [
        'student_id',
        'instructor_id',
        'phone',
        'message',
        'status',
        'attempts',
        'error'
    ];
}

