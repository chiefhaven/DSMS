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
    protected $studentCreatedDate;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($student, string $admin)
    {
        $this->student = $student;
        $this->admin = $admin;
        $this->studentCreatedDate = Carbon::parse($this->student->created_at)->format('d F, Y');
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
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Student enrolled',
            'body' => "Student {$this->student->fname} {$this->student->mname} {$this->student->sname} has been enrolled in {$this->student->course->name} course.",
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
            'body' => "Student {$this->student->fname} {$this->student->mname} {$this->student->sname} has been registerd by {$this->admin}.",
            'student_id' => $this->student->id,
            'url' => url("/viewstudent/{$this->student->id}"),
            'created_at' => now(),
        ];
    }
}
