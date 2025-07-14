<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class bulkAttendance extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $casts = ['attendance_date'=>'datetime'];

    protected $fillable = [
        'administrator_id',
        'student_id',
        'lesson_id'
    ];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'attendances', 'bulk_attendance_id', 'student_id')->withPivot('lesson_id');
    }

    public function Administrator()
    {
       return $this->belongsTo(Administrator::class);
    }


}
