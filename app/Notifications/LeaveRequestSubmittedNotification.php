<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestSubmittedNotification extends Notification
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
        $employee = $leave->employee_name ?? 'Employee';
        $type = $leave->leaveType?->name ?? 'Leave';
        $period = $this->period($leave);

        return (new MailMessage)
            ->subject('Leave request submitted: ' . $employee)
            ->line('A leave request has been submitted and is pending review.')
            ->line('Employee: ' . $employee)
            ->line('Leave type: ' . $type)
            ->line('Period: ' . $period)
            ->line('Days: ' . ($leave->days ?? '-'))
            ->action('Review leave requests', route('leaves.index'))
            ->line('Please review this request in HRM Leaves.');
    }

    public function toDatabase($notifiable): array
    {
        $leave = $this->leaveRequest;
        $employee = $leave->employee_name ?? 'Employee';
        $type = $leave->leaveType?->name ?? 'Leave';
        $period = $this->period($leave);

        return [
            'title' => 'Leave request submitted',
            'body' => $employee . ' submitted a ' . $type . ' request for ' . $period . ' (' . ($leave->days ?? '-') . ' day(s)).',
            'url' => route('leaves.index'),
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