<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseType extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    public function expenseTypeOptions()
    {
        return $this->hasMany(ExpenseTypeOption::class, 'expense_type_id');
    }
}
