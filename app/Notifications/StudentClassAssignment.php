<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentClassAssignment extends Notification
{
    use Queueable;

    protected $classRoom;
    protected $type;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($classRoom, $type)
    {
        $this->classRoom = $classRoom;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
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
            ->subject('You have been assigned a class room')
            ->line('You have been assigned classroom {$classRoom->name}.')
            ->action('View', url('/notifications'))
            ->line('If you have any questions, feel free to reach out.')
            ->salutation('Warm regards');
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->type == 'assign' ? 'You have been assigned a classroom' : 'You have been un-assigned from a class room',
            'body' => $this->type == 'un-assign'
                ? "You have been un-assigned from class room: {$this->classRoom->name}." : "You have been assigned class room {$this->classRoom->name}.",
            'student_id' => $this->classRoom ? $this->classRoom->id : null,
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
            //
        ];
    }
}

