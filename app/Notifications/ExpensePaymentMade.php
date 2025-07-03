<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Bus\Queueable;
use Carbon\Carbon;
use App\Notifications\Channels\SmsChannel;
use App\Http\Controllers\havenUtils as ControllersHavenUtils;

class ExpensePaymentMade extends Notification
{
    use Queueable;

    protected $student;
    protected $expense;
    protected $expensePayment;
    protected $expenseTypeName;

    /**
     * Create a new notification instance.
     */
    public function __construct($student, $expense, $expensePayment)
    {
        $this->student = $student;
        $this->expense = $expense;
        $this->expensePayment = $expensePayment;

        // Efficient: resolve once
        $this->expenseTypeName = ControllersHavenUtils::getExpenceTypeOption($expense->pivot->expense_type) ?? 'Unknown Expense Type';
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
            number_format($this->expensePayment->amount, 2),
            $this->expenseTypeName,
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
            ->line("A payment of K" . number_format($this->expensePayment->amount, 2) . " has been recorded for the expense: {$this->expenseTypeName}.")
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
            'body' => "Payment of K" . number_format($this->expensePayment->amount, 2) . " recorded for: {$this->expenseTypeName}.",
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
            'body' => "Payment of K" . number_format($this->expensePayment->amount, 2) . " has been made for {$this->expenseTypeName}.",
            'student_id' => $this->student->id,
            'url' => url("/expense-payment-receipt/{$this->expense->pivot->id}"),
            'created_at' => now(),
        ];
    }
}
