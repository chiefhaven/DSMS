<?php

namespace App\Notifications;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LessonScheduled extends Notification implements ShouldQueue
{
    use Queueable;

    protected $student;
    protected $instructor;
    protected $schedule;
    protected $scheduleDate;

    /**
     * Create a new notification instance.
     *
     * @param  mixed  $student
     * @param  mixed  $instructor
     * @param  mixed  $schedule
     * @return void
     */
    public function __construct($schedule)
    {
        $this->student = $schedule->student;
        $this->instructor = $schedule->instructor;
        $this->schedule = $schedule;
        $this->scheduleDate = Carbon::parse($schedule->start_time);
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
        try {
            return (new MailMessage)
                ->subject('Lesson Scheduled: ' . $this->schedule->lesson?->name)
                ->greeting('Hello ' . $this->student->name . '!')
                ->line('Your lesson has been scheduled with ' . $this->instructor->fname.' '.$this->instructor->sname)
                ->line('Lesson: ' . $this->schedule->lesson?->name)
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

    /**
     * Get the database representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        try {
            return [
                'title' => 'New Lesson Scheduled',
                'body' => sprintf(
                    "Lesson: %s with %s on %s at %s",
                    $this->schedule->lesson?->name ?? 'N/A',
                    $this->instructor->fname ?? 'Instructor',
                    $this->scheduleDate->format('F j, Y'),
                    $this->scheduleDate->format('g:i A')
                ),
                'student_id' => $this->student->id ?? null,
                'schedule_id' => $this->schedule->id ?? null,
                'url' => url('/student/schedule'),
                'created_at' => now()->toDateTimeString(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create database notification: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the array representation of the notification (for broadcasting).
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}