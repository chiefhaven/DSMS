<?php
namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);
        $phoneNumber = $notifiable->phone ?? null;

        if (!$phoneNumber) {
            Log::error("SMS Error: No phone number found for the recipient.");
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.clickmobile.token'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post(config('services.clickmobile.url'), [
                'to' => $phoneNumber,
                'message' => $message,
                'from' => config('services.clickmobile.from'),
            ]);

            if ($response->failed()) {
                Log::error("SMS Failed: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Error sending SMS: " . $e->getMessage());
        }
    }
}
