<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SmsFailedNotification extends Notification
{
    use Queueable;

    public function __construct(public int $reportId) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('🚨 SMS Delivery Failed')
            ->line('An SMS failed to send.')
            ->line('Report ID: ' . $this->reportId)
            ->action('View Reports', url('/admin/sms-reports'));
    }
}

