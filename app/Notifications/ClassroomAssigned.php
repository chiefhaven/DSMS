<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ClassroomAssigned extends Notification
{
    use Queueable;

    protected $student;
    protected $instructor;
    protected $classroom;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($classroom)
    {
        $this->student      = $schedule->student ?? null;
        $this->instructor   = $schedule->instructor ?? null;
        $this->classroom     = $classroom;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database', SmsChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        try {
            return (new MailMessage)
                ->subject('Lesson Scheduled: ' . ($this->pivotData['lesson_name'] ?? 'A Lesson'))
                ->greeting('Hello ' . ($this->student->fname ?? 'Student') . '!')
                ->line('Your lesson has been scheduled with ' . ($this->instructor->fname ?? '') . ' ' . ($this->instructor->sname ?? ''))
                ->line('Lesson: ' . ($this->pivotData['lesson_name'] ?? 'N/A'))
                ->line('Location: ' . ($this->pivotData['location'] ?? 'N/A'))
                ->line('Status: ' . ($this->pivotData['status'] ?? 'scheduled'))
                ->line('Date: ' . $this->scheduleDate->format('l, F j, Y'))
                ->line('Time: ' . $this->scheduleDate->format('g:i A'))
                ->action('View Schedule', url('/student/schedule'))
                ->line('Thank you for using our platform!');
        } catch (\Exception $e) {
            Log::error('Failed to generate lesson scheduled email: ' . $e->getMessage());
            return (new MailMessage)
                ->subject('Lesson Scheduled')
                ->line('A new lesson has been scheduled for you.');
        }
    }

    public function toSms($notifiable)
    {
        return sprintf(
            "Lesson Scheduled: %s with instructor %s on %s at %s. Location: %s. Check: %s",
            $this->pivotData['lesson'] ?? 'N/A',
            $this->instructor->fname ?? 'Instructor',
            $this->scheduleDate->format('F j, Y'),
            $this->scheduleDate->format('g:i A'),
            $this->pivotData['location'] ?? 'N/A',
            url('/student/schedule')
        );
    }

    public function toDatabase($notifiable)
    {
        try {
            return [
                'title'       => 'New Lesson Scheduled',
                'body'        => sprintf(
                    "Lesson: %s with %s on %s at %s",
                    $this->pivotData['lesson'] ?? 'N/A',
                    $this->instructor->fname ?? 'Instructor',
                    $this->scheduleDate->format('F j, Y'),
                    $this->scheduleDate->format('g:i A')
                ),
                'student_id'  => $this->student->id ?? null,
                'schedule_id' => $this->schedule->id ?? null,
                'lesson_id'   => $this->pivotData['lesson_id'] ?? null,
                'location'    => $this->pivotData['location'] ?? null,
                'status'      => $this->pivotData['status'] ?? 'scheduled',
                'url'         => url('/student/schedule'),
                'created_at'  => now()->toDateTimeString(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create database notification: ' . $e->getMessage());
            return [];
        }
    }

    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}
