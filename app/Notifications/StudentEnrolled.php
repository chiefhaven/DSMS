<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentEnrolled extends Notification
{
    use Queueable;

    protected $student;
    protected $admin;
    protected $superAdmin;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($student, $superAdmin)
    {
        $this->student = $student;
        $this->superAdmin = $superAdmin;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New Student Enrollment Notification')
            ->greeting('Hello!')
            ->line("Student {$this->student->fname} {$this->student->mname} {$this->student->sname} has been successfully enrolled in the {$this->student->course->name} course.")
            ->action('View Student Details', url("/viewstudent/{$this->student->id}"))
            ->line('Thank you.');
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'Enrollment',
            'title' => 'Student Enrolled',
            'body' => "Student {$this->student->fname} {$this->student->mname} {$this->student->sname} has been enrolled in the {$this->student->course->name} course.",
            'student_id' => $this->student->id,
            'url' => url("/viewstudent/{$this->student->id}"),
            'created_at' => now(),
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title' => 'Student registered',
            'body' => "Student {$this->student->fname} {$this->student->mname} {$this->student->sname} has been enrolled by {$this->admin}.",
            'student_id' => $this->student->id,
            'url' => url("/viewstudent/{$this->student->id}"),
            'created_at' => now(),
        ];
    }
}
