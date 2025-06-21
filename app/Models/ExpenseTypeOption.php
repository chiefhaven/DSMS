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
        'is_active',
    ];

    public function expenseType()
    {
        return $this->belongsTo(expenseType::class, 'expense_type_id');
    }
}
