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
    ];

    public function expenseType()
    {
        return $this->belongsTo(expenseType::class, 'expense_type_id');
    }
}
