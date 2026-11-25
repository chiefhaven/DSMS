<?php

namespace App\Notifications;

use App\Models\Student;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentTrnChangeRejectedNotification extends Notification
{
    public function __construct(private Student $student)
    {
    }

    public function via($notifiable): array
    {
        return $notifiable->email ? ['database', 'mail'] : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Student TRN change rejected')
            ->line('Your TRN change request for ' . $this->studentName() . ' was rejected.')
            ->action('View student', route('students.show', $this->student->id));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'TRN change rejected',
            'body' => 'Your TRN change request for ' . $this->studentName() . ' was rejected.',
            'student_id' => $this->student->id,
            'url' => route('students.show', $this->student->id),
            'created_at' => now(),
        ];
    }

    private function studentName(): string
    {
        return trim($this->student->fname . ' ' . ($this->student->mname ?? '') . ' ' . $this->student->sname);
    }
}
