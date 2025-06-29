<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Expense extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'date_approved' => 'datetime'
    ];

    public function Students()
    {
        return $this->belongsToMany(Student::class)->withPivot('id', 'expense_type', 'repeat', 'status', 'paid_at', 'payment_entered_by','amount', 'payment_method', 'balance', 'paid')
            ->withTimestamps();
    }

    public function Administrator()
    {
        return $this->belongsTo(Administrator::class, 'added_by');
    }

    public function expenseTypeOption()
    {
        return $this->belongsTo(ExpenseTypeOption::class, 'expense_type', 'id');
    }

    //delete relationships!
    protected static function booted () {
        static::deleting(function(Expense $expense) {
             $expense->students()->detach();
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Expense slated for {$this->group} " .
                "{$eventName}."
            );
    }
}
