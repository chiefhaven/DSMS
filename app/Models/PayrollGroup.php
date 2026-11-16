<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PayrollGroup extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['name', 'payment_month', 'created_by'];

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('payroll')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Payroll group {$this->name} {$eventName}."
            );
    }
}
