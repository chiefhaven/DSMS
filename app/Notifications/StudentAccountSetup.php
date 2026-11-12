<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentAccountSetup extends Notification
{
    public function __construct(private string $url, private int $expiresIn)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Set up your student portal account')
            ->line('Your student portal login is '.$notifiable->email.'.')
            ->line('Use the link below to set your password. The link expires in '.$this->expiresIn.' minutes.')
            ->action('Set password', $this->url)
            ->line('If the link expires, request another from the sign-in page.');
    }
}
