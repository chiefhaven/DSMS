<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpensePayment extends Model
{
    use HasFactory, HasUuids;
    protected $table = 'expense_payments';

    public function student() {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function expense() {
        return $this->belongsTo(Expense::class, 'expense_id');
    }

    public function paymentUser()
    {
        return $this->belongsTo(User::class, 'payment_entered_by');
    }

    public function enteredByAdmin()
    {
        return $this->paymentUser->administrator;
    }
}
