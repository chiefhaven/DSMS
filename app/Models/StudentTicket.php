<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentTicket extends Model
{
    use HasUuids;

    public const CATEGORIES = [
        'general' => 'General enquiry',
        'enrollment' => 'Enrollment',
        'payments' => 'Payments',
        'lessons' => 'Lessons',
        'documents' => 'Documents',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'waiting_on_student' => 'Waiting on student',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];

    protected $fillable = [
        'student_id',
        'assigned_to',
        'category',
        'subject',
        'status',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(StudentTicketMessage::class, 'ticket_id')
            ->orderBy('created_at');
    }
}
