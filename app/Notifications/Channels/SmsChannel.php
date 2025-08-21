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
        $phoneNumber = $notifiable->student->phone ?? null;

        if (!$phoneNumber) {
            Log::error("SMS Error: No phone number found for the recipient.");
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => config('services.smsApi.token'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post(config('services.smsApi.url') . '/send-sms', [
                'to' => $phoneNumber,
                'message' => $message,
                'from' => config('services.smsApi.from'),
            ]);

            Log::info("SMS Sent: " . $response->body());

            if ($response->failed()) {
                Log::error("SMS Failed: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Error sending SMS: " . $e->getMessage());
        }
    }
}
