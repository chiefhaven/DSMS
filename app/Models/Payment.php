<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['amount_paid'];

    public function Student()
    {
       return $this->belongsTo(Student::class);
    }

    public function PaymentMethod()
    {
       return $this->belongsTo(PaymentMethod::class);
    }
}
