<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayslipIssued extends Notification
{
    public function __construct(
        private string $monthLabel,
        private string $pdfContent,
        private string $fileName,
        private string $netPay
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your payslip for ' . $this->monthLabel)
            ->line('Please find your payslip for ' . $this->monthLabel . ' attached to this email.')
            ->line('Net pay: K' . $this->netPay . '.')
            ->line('If anything looks incorrect, contact the administration office.')
            ->attachData($this->pdfContent, $this->fileName, ['mime' => 'application/pdf']);
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Payslip issued',
            'body' => 'Your payslip for ' . $this->monthLabel . ' was sent to ' . $notifiable->email . '.',
            'url' => url('/notifications'),
            'created_at' => now(),
        ];
    }
}
