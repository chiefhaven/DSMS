<?php

namespace App\Notifications;

use App\Models\Student;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentTrnApprovalRequestedNotification extends Notification
{
    public function __construct(
        private Student $student,
        private string $requesterName
    ) {
    }

    public function via($notifiable): array
    {
        return $notifiable->email ? ['database', 'mail'] : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Student TRN change awaiting approval')
            ->line('A TRN change for ' . $this->studentName() . ' was submitted by ' . $this->requesterName . '.')
            ->action('Review TRN changes', route('students.trn-approvals.index'));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'TRN change awaiting approval',
            'body' => 'A TRN change for ' . $this->studentName() . ' was submitted by ' . $this->requesterName . '.',
            'student_id' => $this->student->id,
            'url' => route('students.trn-approvals.index'),
            'created_at' => now(),
        ];
    }

    private function studentName(): string
    {
        return trim($this->student->fname . ' ' . ($this->student->mname ?? '') . ' ' . $this->student->sname);
    }
}
