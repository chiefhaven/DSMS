<?php

declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class Invoice extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $casts = ['invoice_payment_due_date'=>'datetime', 'date_created'=>'datetime'];

    public function Student()
    {
       return $this->belongsTo(Student::class);
    }

    public function User()
    {
       return $this->belongsTo(User::class);
    }

    public function Course()
    {
       return $this->belongsTo(Course::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Invoice number {$this->invoice_number} " .
                "{$eventName}."
            );
    }


}
