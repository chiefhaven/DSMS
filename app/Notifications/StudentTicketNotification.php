<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentTicketNotification extends Notification
{
    public function __construct(
        private string $title,
        private string $body,
        private string $url,
        private string $ticketId
    ) {
    }

    public function via($notifiable): array
    {
        return $notifiable->email ? ['database', 'mail'] : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->body)
            ->action('Open ticket', $this->url);
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'ticket_id' => $this->ticketId,
            'created_at' => now(),
        ];
    }
}
