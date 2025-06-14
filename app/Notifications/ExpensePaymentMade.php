<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Carbon\Carbon;
use App\Notifications\Channels\SmsChannel;

class ExpensePaymentMade extends Notification
{
    use Queueable;

    protected $student;
    protected $expense;

    /**
     * Create a new notification instance.
     */
    public function __construct($student, $expense)
    {
        $this->student = $student;
        $this->expense = $expense;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail', 'database', SmsChannel::class];
    }

    /**
     * Format SMS message.
     */
    public function toSms($notifiable)
    {
        return sprintf(
            "RTD payment receipt:\nName: %s %s\nAmount: K%s\nExpense: %s\nView: %s",
            $this->student->fname,
            $this->student->sname,
            number_format($this->expense->pivot->amount, 2),
            $this->expense->pivot->expense_type,
            url("/expense-payment-receipt/{$this->expense->pivot->id}")
        );
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Receipt for RTD Payment')
            ->greeting("Hello {$this->student->fname} {$this->student->sname},")
            ->line("A payment of K" . number_format($this->expense->pivot->amount, 2) . " has been recorded for the expense: {$this->expense->pivot->expense_type}.")
            ->action('View Receipt', url("/expense-payment-receipt/{$this->expense->pivot->id}"))
            ->line('Thank you for enrolling with Daron!');
    }

    /**
     * Store notification in the database.
     */
    public function toDatabase($notifiable)
    {
        return [
            'title' => 'RTD Payment Made',
            'body' => "Payment of K" . number_format($this->expense->pivot->amount, 2) . " recorded for: {$this->expense->pivot->expense_type}.",
            'student_id' => $this->student->id,
            'url' => url("/expense-payment-receipt/{$this->expense->pivot->id}"),
            'created_at' => now(),
        ];
    }

    /**
     * For broadcast or generic array usage.
     */
    public function toArray($notifiable)
    {
        return [
            'title' => 'RTD Payment Receipt',
            'body' => "Payment of K" . number_format($this->expense->pivot->amount, 2) . " has been made for {$this->expense->pivot->expense_type}.",
            'student_id' => $this->student->id,
            'url' => url("/expense-payment-receipt/{$this->expense->pivot->id}"),
            'created_at' => now(),
        ];
    }
}