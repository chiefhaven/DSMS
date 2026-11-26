<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FleetDueNotification extends Notification
{
    public function __construct(
        private int $fleetId,
        private string $vehicleLabel,
        private string $dueType,
        private string $dueItem,
        private string $url
    ) {
    }

    public function via($notifiable): array
    {
        return $notifiable->email ? ['database', 'mail'] : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Fleet reminder: ' . $this->vehicleLabel)
            ->line($this->body())
            ->action('View fleet reminder', $this->url);
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Fleet dues need attention',
            'body' => $this->body(),
            'url' => $this->url,
            'fleet_id' => $this->fleetId,
            'due_type' => $this->dueType,
            'due_item' => $this->dueItem,
            'created_at' => now(),
        ];
    }

    private function body(): string
    {
        return $this->vehicleLabel . ': ' . $this->dueItem . '.';
    }
}
