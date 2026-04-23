<?php
namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);

        // Flexible phone resolution — works for User, Student, or any notifiable
        $phoneNumber = $notifiable->phone
            ?? $notifiable->student?->phone
            ?? null;

        if (!$phoneNumber) {
            Log::warning('SMS skipped: no phone number found.', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id'   => $notifiable->getKey(),
                'notification'    => get_class($notification),
            ]);
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.smsApi.token'),
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])
            ->post(config('services.smsApi.url') . '/sms/send', [
                'message'    => $message,
                'type'       => 'plain',
                'sender_id'  => config('services.smsApi.from'),
                'recipient'  => $phoneNumber,
            ]);

            if ($response->successful()) {
                Log::info('SMS sent successfully.', [
                    'notifiable_id' => $notifiable->getKey(),
                    'phone'         => $phoneNumber,
                    'response'      => $response->body(),
                ]);
            } else {
                Log::error('SMS dispatch failed.', [
                    'notifiable_id' => $notifiable->getKey(),
                    'phone'         => $phoneNumber,
                    'status'        => $response->status(),
                    'response'      => $response->body(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('SMS exception during dispatch.', [
                'notifiable_id' => $notifiable->getKey(),
                'phone'         => $phoneNumber,
                'error'         => $e->getMessage(),
            ]);
        }
    }
}
