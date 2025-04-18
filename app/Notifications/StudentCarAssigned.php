<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudentCarAssigned extends Notification
{
    use Queueable;

    protected $fleet;
    protected $type;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($fleet, $type)
    {
        $this->fleet = $fleet;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
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
            ->subject('You have been assigned a vehicle')
            ->line('You have been assigned vehicle {$fleet->car_brand_model}.')
            ->action('View', url('/notifications'))
            ->line('If you have any questions, feel free to reach out.')
            ->salutation('Warm regards');
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->type == 'assign' ? 'You have been assigned a vehicle' : 'You have been un-assigned from a vehicle',
            'body' => $this->type == 'un-assign'
                ? "You have been un-assigned from vehicle {$this->fleet->car_brand_model} reg number {$this->fleet->car_registration_number}." : "You have been assigned vehicle {$this->fleet->car_brand_model} reg number {$this->fleet->car_registration_number}.",
            'fleet_id' => $this->fleet ? $this->fleet->id : null,
            'url' => url("/"),
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
