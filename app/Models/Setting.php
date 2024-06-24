<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $casts = [
        'attendance_time_stop' => 'datetime',
        'attendance_time_start' => 'datetime'
    ];

    public function District()
    {
       return $this->belongsTo(District::class);
    }
}
