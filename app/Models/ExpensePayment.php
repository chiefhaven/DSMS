<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpensePayment extends Model
{
    protected $table = 'expense_student';

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
