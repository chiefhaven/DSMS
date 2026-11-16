<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payroll extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'instructor_id',
        'administrator_id',
        'payroll_group_id',
        'payment_month',
        'basic_salary',
        'allowances',
        'bonus',
        'bonus_attendances',
        'deductions',
        'net_pay',
        'payment_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'bonus' => 'decimal:2',
        'bonus_attendances' => 'integer',
        'deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
    ];

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function administrator()
    {
        return $this->belongsTo(Administrator::class);
    }

    public function group()
    {
        return $this->belongsTo(PayrollGroup::class, 'payroll_group_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getEmployeeNameAttribute(): string
    {
        $employee = $this->instructor ?? $this->administrator;

        return $employee ? trim($employee->fname . ' ' . $employee->sname) : '-';
    }

    public function getEmployeeTypeAttribute(): string
    {
        return $this->instructor_id ? 'Instructor' : 'Administrator';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('payroll')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Payroll record for {$this->payment_month} {$eventName}."
            );
    }
}
