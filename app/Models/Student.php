<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = ['_token', 'fname', 'sname', 'trn', 'date_of_birth', 'phone', 'gender', 'address', 'district'];


    public function Account()
    {
       return $this->hasOne(Account::class);
    }

    public function Enrollment()
    {
       return $this->hasOne(Enrollment::class);
    }

    public function User()
    {
       return $this->hasOne(User::class);
    }

    public function Fleet()
    {
       return $this->belongsTo(Fleet::class);
    }

    public function Course()
    {
       return $this->belongsTo(Course::class);
    }

    public function Invoice()
    {
       return $this->hasOne(Invoice::class);
    }

    public function Attendance()
    {
       return $this->hasMany(Attendance::class);
    }

    public function District()
    {
       return $this->belongsTo(District::class);
    }

    public function Payment()
    {
       return $this->hasMany(Payment::class);
    }
}
