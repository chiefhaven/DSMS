<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;


class StudentAddedToExamList extends Notification
{
    use Queueable;

    protected $student;
    protected $expense;
    protected $formattedDate;
    protected $expenseType = 'Exam';

    /**
     * Create a new notification instance.
     *
     * @return void
     */

     public function __construct($student, $expense)
     {
         $this->student = $student;
         $this->expense = $expense;

         try {
             $this->formattedDate = Carbon::parse($this->expense->group)->format('d F, Y');
         } catch (\Exception $e) {
             $this->formattedDate = null;
             Log::warning("Invalid date format in expense group: {$this->expense->group}");
         }

         // Use pivot expense_type if available, fallback to "Exam"
         $this->expenseType = $student->pivot->expense_type ?? 'Exam';
     }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'mail', SmsChannel::class];
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


    public function toSms($notifiable)
    {
        return sprintf(
            "You have been added to an expense/exam list%s\nName: %s %s\nAmount: K%s\nExpense: %s\nPlease go to office and collect your cash",
            $this->formattedDate ? " slated on {$this->formattedDate}" : '',
            $this->student->fname,
            $this->student->sname,
            number_format($this->student->pivot->amount, 2),
            $this->expenseType
        );
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'Added to exam list',
            'body' => $this->formattedDate
                ? "You have been added to exam list slated for {$this->formattedDate}."
                : "You have been added to an exam list.",
            'expense_id' => $this->expense->id,
            'student_id' => $this->student->id,
            'amount' => $this->student->pivot->amount,
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
