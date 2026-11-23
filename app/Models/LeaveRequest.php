<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'instructor_id',
        'administrator_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function administrator()
    {
        return $this->belongsTo(Administrator::class);
    }

    public function employee()
    {
        return $this->instructor ?: $this->administrator;
    }

    public function getEmployeeNameAttribute(): string
    {
        $employee = $this->employee();
        if (!$employee) {
            return 'Unknown';
        }
        return trim(($employee->fname ?? '') . ' ' . ($employee->mname ?? '') . ' ' . ($employee->sname ?? '')) ?: 'Unknown';
    }

    public function getEmployeeTypeAttribute(): string
    {
        if ($this->administrator_id) {
            return 'Administrator';
        }
        if ($this->instructor_id) {
            return 'Instructor';
        }
        return 'Employee';
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
