<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentAddedToExamList extends Notification
{
    use Queueable;

    protected $student;
    protected $expense;
    protected $formattedDate;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($student, $expense)
    {
        $this->student = $student;
        $this->expense = $expense;
        $this->formattedDate = Carbon::parse($this->expense->date)->format('d F, Y');}

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
            'title' => 'Added to exam list',
            'body' => "You have been added to exam list slated for {$this->formattedDate}.",
            'expense_id' => $this->expense->id,
            'url' => url("#"),
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
