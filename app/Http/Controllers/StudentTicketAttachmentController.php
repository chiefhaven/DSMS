<?php

namespace App\Http\Controllers;

use App\Models\StudentTicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentTicketAttachmentController extends Controller
{
    public function download(Request $request, StudentTicketAttachment $attachment)
    {
        $attachment->load('message.ticket');
        $ticket = $attachment->message?->ticket;
        abort_unless($ticket, 404);

        $user = $request->user();
        $isTicketStudent = $user->hasRole('student')
            && $user->can('view student tickets')
            && $user->Student?->id === $ticket->student_id;
        $canViewTicket = $user->can('view all student tickets') || $user->can('manage student tickets');
        abort_unless($isTicketStudent || $canViewTicket, 404);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
