<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class InstructorPayment extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'instructor_payments';

    protected $fillable = [
        'instructor_id',
        'attendances_count',
        'pay_per_attendance',
        'total_payment',
        'payment_date',
        'payment_month',
        'status',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'attendances_count' => 'integer',
        'pay_per_attendance' => 'decimal:2',
        'total_payment' => 'decimal:2',
    ];

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('instructor_payment')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Instructor payment record was {$eventName}."
            );
    }
}
