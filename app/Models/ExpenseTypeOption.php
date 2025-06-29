<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseTypeOption extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'amount_per_student',
        'expense_type_id',
        'fees_percent_threshhold',
        'is_active',
    ];

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class, 'expense_type_id'); //case sensitivity
    }

    public function expense()
    {
        return $this->hasMany(Expense::class, 'expense_type_id'); //case sensitivity
    }
}
