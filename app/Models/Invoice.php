<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $casts = ['invoice_payment_due_date'=>'datetime', 'date_created'=>'datetime'];

    public function Student()
    {
       return $this->belongsTo(Student::class);
    }

    public function User()
    {
       return $this->belongsTo(User::class);
    }

    public function Course()
    {
       return $this->belongsTo(Course::class);
    }

}
