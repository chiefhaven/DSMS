<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalaryGrade extends Model
{
    use HasUuids, HasFactory, LogsActivity, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['code', 'name', 'description', 'basic_salary'];

    protected $casts = [
        'basic_salary' => 'decimal:2',
    ];

    public function administrators(): HasMany
    {
        return $this->hasMany(Administrator::class, 'salary_grade_id');
    }

    public function instructors(): HasMany
    {
        return $this->hasMany(Instructor::class, 'salary_grade_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) =>
                "Salary grade {$this->code} {$eventName}."
            );
    }
}
