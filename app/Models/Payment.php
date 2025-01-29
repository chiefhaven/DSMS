<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = ['amount_paid'];

    public function Student()
    {
       return $this->belongsTo(Student::class);
    }

    public function PaymentMethod()
    {
       return $this->belongsTo(PaymentMethod::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Payement with transaction id  {$this->transaction_id} " .
                "{$eventName}."
            );
    }
}
