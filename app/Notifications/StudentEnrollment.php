<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentEnrollment extends Notification
{
    use Queueable;

    protected $student;
    protected $studentCreatedDate;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($student)
    {
        $this->student = $student;
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
            ->subject('You have been enrolled')
            ->line('You have been enrolled in {$this->student->course->name}.')
            ->action('View', url('/notifications'))
            ->line('If you have any questions, feel free to reach out.')
            ->salutation('Warm regards');
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'You have been enrolled',
            'body' => "You have been enrolled in {$this->student->course->name}.",
            'student_id' => $this->student->id,
            'url' => url("/dashboard"),
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
            //
        ];
    }
}
