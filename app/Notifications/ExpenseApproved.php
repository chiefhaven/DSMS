<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseApproved extends Notification
{
    use Queueable;

    protected $expense;
    protected $admin;
    protected $formattedDate;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($expense, string $admin)
    {
        $this->expense = $expense;
        $this->admin = $admin;
        $this->formattedDate = Carbon::createFromFormat('d/m/Y', $this->expense->group)->format('d F, Y');


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
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Expense approved',
            'body' => "An expense slated for {$this->formattedDate} has been approved by {$this->admin}.",
            'expense_id' => $this->expense->id,
            'url' => url("/expenses"),
            'created_at' => now(),
        ];
    }

    // Or use `toArray`:
    public function toArray($notifiable)
    {
        return [
            'title' => 'Expense Added',
            'body' => "An expense slated for {$this->formattedDate} has been approved by {$this->admin}.",
            'expense_id' => $this->expense->id,
            'url' => url("/review-expense/{$this->expense->id}"),
            'created_at' => now(),
        ];
    }
}
