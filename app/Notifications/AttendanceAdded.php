<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AttendanceAdded extends Notification
{
    use Queueable;

    protected $student;
    protected $admin;
    protected $attendanceCreatedDate;
    protected $attendance;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($student, $attendance, String $admin)
    {
        $this->student = $student;
        $this->admin = $admin;
        $this->attendance = $attendance;
        $this->attendanceCreatedDate = Carbon::parse($attendance->created_at)->format('d F, Y');
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
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
            'title' => 'Attendance entered',
            'body' => "Your attendance for lesson {$this->attendance->lesson->name} has been entered by {$this->admin}.",
            'student_id' => $this->student->id,
            'url' => url("/"),
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
            'title' => 'Attendance entered',
            'body' => "Your attendance; {$this->attendance->lesson->name} has been entered by {$this->attendance->administrator->fname}.",
            'student_id' => $this->student->id,
            'url' => url("/viewstudent/{$this->student->id}"),
            'created_at' => now(),
        ];
    }
}

