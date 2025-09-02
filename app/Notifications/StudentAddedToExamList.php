<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class StudentAddedToExamList extends Notification
{
    use Queueable;

    protected $student;
    protected $expense;
    protected $formattedDate;
    protected $expenseType;

    /**
     * Create a new notification instance.
     */
    public function __construct($student, $expense)
    {
        $this->student = $student;
        $this->expense = $expense;

        // Safely format the date
        try {
            $this->formattedDate = $this->expense->group;
        } catch (\Exception $e) {
            try {
                $this->formattedDate = Carbon::parse($this->expense->group)->format('d F, Y');
            } catch (\Exception $e) {
                $this->formattedDate = null; // fallback if all fails
                Log::warning("Invalid date format for expense group: {$this->expense->group}");
            }
        }

        // Use pivot expense_type if available, fallback to "Exam"
        $this->expenseType = $student->pivot->expense_type ?? 'Exam';
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database', 'mail', SmsChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Added to Exam List')
            ->greeting("Hello {$this->student->fname},")
            ->line("You have been added to an exam list " . ($this->formattedDate ? "scheduled for {$this->formattedDate}" : ''))
            ->line("Amount: K" . number_format($this->student->pivot->amount, 2))
            ->line("Expense: {$this->expenseType}")
            ->action('View Details', url("#"))
            ->line('Please go to the office and collect your cash.');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms($notifiable)
    {
        return sprintf(
            "You have been added to an exam list %s\nName: %s %s\nAmount: K%s\nExpense type: \nPlease go to office and collect your cash before date expires.",
            $this->formattedDate ? "scheduled on {$this->formattedDate}" : '',
            $this->student->fname,
            $this->student->sname,
            number_format($this->student->pivot->amount, 2),
        );
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Added to exam list',
            'body' => $this->formattedDate
                ? "You have been added to an exam list scheduled for {$this->formattedDate}."
                : "You have been added to an exam list.",
            'expense_id'   => $this->expense->id,
            'student_id'   => $this->student->id,
            'amount'       => $this->student->pivot->amount,
            'expense_type' => $this->expenseType,
            'url'          => url("#"),
            'created_at'   => now(),
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}
