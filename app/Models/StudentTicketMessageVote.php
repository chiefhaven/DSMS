<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTicketMessageVote extends Model
{
    protected $fillable = [
        'message_id',
        'student_id',
        'rating',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(StudentTicketMessage::class, 'message_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
