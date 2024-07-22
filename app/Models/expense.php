<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class expense extends Model
{
    use HasFactory;

    public function student()
    {
        return $this->belongsToMany(Student::class);
    }

    //delete relationships!
    protected static function booted () {
        static::deleting(function(Expense $expense) {
             $expense->student()->detach();
        });
    }
}
