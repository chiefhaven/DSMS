<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseCreated extends Notification
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
        $this->formattedDate = Carbon::createFromFormat('m/d/Y', $this->expense->group)->format('d F, Y');
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
            ->subject('New expense created')
            ->line("An expense slated for {$this->formattedDate} has been created by {$this->admin}.")
            ->action('View Notification', url('/expenses'))
            ->line('If you have any questions, feel free to reach out.')
            ->salutation('Warm regards');
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
            'title' => 'Expense Added',
            'body' => "An expense slated for {$this->formattedDate} has been created by {$this->admin}.",
            'expense_id' => $this->expense->id,
            'url' => url("/review-expense/{$this->expense->id}"),
            'created_at' => now(),
        ];
    }

    // Or use `toArray`:
    public function toArray($notifiable)
    {
        return [
            'title' => 'Expense Added',
            'body' => "An expense slated for {$this->formattedDate} has been created by {$this->admin}.",
            'expense_id' => $this->expense->id,
            'url' => url("/review-expense/{$this->expense->id}"),
            'created_at' => now(),
        ];
    }
}
