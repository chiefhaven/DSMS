<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentClassAssignment extends Notification implements ShouldQueue
{
    use Queueable;

    protected $classRoom;
    protected $student;
    protected $instructor;
    protected $type;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($classRoom, $student)
    {
        $this->classRoom = [
            'name' => $classRoom->name,
        ];

        $this->student = [
            'fname' => $student->fname,
            'mname' => $student->mname,
            'sname' => $student->sname,
        ];

        $firstInstructor = $classRoom->instructors[0] ?? null;

        $this->instructor = [
            'fname' => $firstInstructor->fname ?? '',
            'sname' => $firstInstructor->sname ?? '',
            'phone' => $firstInstructor->phone ?? '',
        ];

        $this->type = 'assign';
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
        return (new MailMessage)
            ->subject("Assigned to Classroom: {$this->classRoom['name']}")
            ->line("You have been assigned to classroom {$this->classRoom['name']} with Instructor {$this->instructor['fname']} {$this->instructor['sname']}.")
            ->action('Download our App to view more', url('/dashboard'))
            ->line('If you have any questions, feel free to reach out.')
            ->salutation('Warm regards, Daron Driving School');

    }

    /**
     * Get the SMS representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    public function toSms($notifiable)
    {
        return sprintf(
            "Dear %s %s %s,\n\nYou have been assigned to classroom (%s).\nContact instructor: %s %s\nPhone: %s\n\nCheck your assignments here:\n%s\n\nIf you encounter any challenges, contact the administrator on +265887226317.\n\nBest regards,\nDARON DRIVING SCHOOL",
            $this->student['fname'] ?? '',
            $this->student['mname'] ?? '',
            $this->student['sname'] ?? '',
            $this->classRoom['name'] ?? '',
            $this->instructor['fname'] ?? '',
            $this->instructor['sname'] ?? 'Instructor',
            $this->instructor['phone'] ?? '+265',
            url('/dashboard')
        );

    }

    /**
     * Get the database representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->type === 'assign' ? 'You have been assigned to a classroom' : 'You have been un-assigned from a classroom',
            'body' => $this->type === 'un-assign'
                ? "You have been un-assigned from classroom: {$this->classRoom['name']}."
                : "You have been assigned to classroom: {$this->classRoom['name']} with Instructor {$this->instructor['fname']} {$this->instructor['sname']}.",
            'student_id' => $notifiable->id,
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
        return $this->toDatabase($notifiable);
    }
}
