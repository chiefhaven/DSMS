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
     *
     * @return void
     */
    public function __construct($student, $expense)
    {
        $this->student = $student;
        $this->expense = $expense;
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
     * Format SMS message.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    public function toSms($notifiable)
    {
        return sprintf(
            "RTD payment reciept: K%s for %s %s to expense #%s. View: %s",
            number_format($this->expense->pivot->amount, 2),
            $this->student->fname,
            $this->student->sname,
            $this->expense->pivot->expense_type,
            url("/")
        );
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
            ->subject('Reciept for RTD payment')
            ->greeting("Hello {$this->student->fname} {$this->student->sname},")
            ->line("You have been paid K" . number_format($this->expense->pivot->amount, 2) . " for expense: {$this->expense->pivot->expense_type}. Name: {$this->student->fname} {$this->student->mname} {$this->student->sname}.")
            ->action('View Payment', url("/"))
            ->line('Thank you for using enrolling with Daron!');
    }

    /**
     * Store in database.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'title' => 'RTD payment made',
            'body' => "You have been paid K" . number_format($this->expense->pivot->amount, 2) . " for expense: {$this->expense->pivot->expense_type}.",
            'student_id' => $this->student->id,
            'url' => url("/"),
            'created_at' => now(),
        ];
    }

    /**
     * For broadcasting or other array uses.
     */
    public function toArray($notifiable)
    {
        return [
            'title' => 'Reciept for RTD payment',
            'body' => "Payment of K" . number_format($this->expense->pivot->amount, 2) . " has been made to {$this->student->fname} {$this->student->mname} {$this->student->sname}.",
            'student_id' => $this->student->id,
            'url' => url("/"),
            'created_at' => now(),
        ];
    }
}
