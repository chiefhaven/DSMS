<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Student extends Model
{
    use HasUuids;
    use HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['_token', 'fname', 'sname', 'trn', 'date_of_birth', 'phone', 'gender', 'address', 'district'];

    public function getFormattedStatusAttribute()
    {
        return $this->status ? Str::title($this->status) : '-';
    }

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

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function Invoice()
    {
       return $this->hasOne(Invoice::class);
    }

    public function attendance()
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

    public function expenses()
    {
        return $this->belongsToMany(Expense::class)->withPivot('expense_type');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id', 'id');
    }

    //Delete relationships!
    protected static function booted () {
        static::deleting(function(Student $student) {
             $student->attendance()->delete();
        });
    }
}
