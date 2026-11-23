<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestReviewedNotification extends Notification
{
    public function __construct(private LeaveRequest $leaveRequest)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $leave = $this->leaveRequest;
        $status = ucfirst((string) ($leave->status ?? 'reviewed'));
        $type = $leave->leaveType?->name ?? 'Leave';
        $period = $this->period($leave);

        $message = (new MailMessage)
            ->subject('Leave request ' . strtolower($status) . ': ' . $type)
            ->line('Your leave request has been reviewed.')
            ->line('Leave type: ' . $type)
            ->line('Period: ' . $period)
            ->line('Days: ' . ($leave->days ?? '-'))
            ->line('Status: ' . $status);

        if (!empty($leave->review_notes)) {
            $message->line('Review notes: ' . $leave->review_notes);
        }

        return $message
            ->action('View My HRM', route('instructor.my-hrm'))
            ->line('You can view your leave request history in My HRM.');
    }

    public function toDatabase($notifiable): array
    {
        $leave = $this->leaveRequest;
        $status = ucfirst((string) ($leave->status ?? 'reviewed'));
        $type = $leave->leaveType?->name ?? 'Leave';
        $period = $this->period($leave);

        $body = 'Your ' . $type . ' request for ' . $period . ' (' . ($leave->days ?? '-') . ' day(s)) was ' . strtolower($status) . '.';
        if (!empty($leave->review_notes)) {
            $body .= ' Notes: ' . $leave->review_notes;
        }

        return [
            'title' => 'Leave request ' . strtolower($status),
            'body' => $body,
            'url' => route('instructor.my-hrm'),
            'created_at' => now(),
        ];
    }

    private function period(LeaveRequest $leave): string
    {
        $start = $leave->start_date ? $leave->start_date->format('Y-m-d') : '-';
        $end = $leave->end_date ? $leave->end_date->format('Y-m-d') : '-';

        return $start . ' to ' . $end;
    }
}