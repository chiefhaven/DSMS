<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class Administrator extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function User()
    {
       return $this->hasOne(User::class);
    }

    public function hrDepartment()
    {
       return $this->belongsTo(HrDepartment::class, 'hr_department_id');
    }

    public function designation()
    {
       return $this->belongsTo(Designation::class, 'designation_id');
    }

    public function salaryGrade()
    {
       return $this->belongsTo(SalaryGrade::class, 'salary_grade_id');
    }

    /**
     * The editable staff salary takes precedence; the salary grade supplies its default when unset.
     */
    public function getEffectiveBasicSalaryAttribute()
    {
        return $this->basic_salary ?? $this->salaryGrade?->basic_salary;
    }

    public function Expense()
    {
       return $this->hasMany(Expense::class);
    }

    public function BulkAttendances()
    {
       return $this->hasMany(bulkAttendance::class);
    }

    public function attendance()
    {
       return $this->hasMany(Attendance::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Administrator {$this->fname} {$this->sname} " .
                "{$eventName}."
            );
    }
}
