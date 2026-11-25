<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentTicketMessage extends Model
{
    use HasUuids;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'body',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(StudentTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(StudentTicketAttachment::class, 'message_id');
    }
}
