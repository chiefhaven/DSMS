<?php
namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    private const DEFAULT_COUNTRY_CODE = '265';

    public function send($notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);

        // Flexible phone resolution — works for User, Student, or any notifiable
        $phoneNumber = $this->normalizePhoneNumber($notifiable->phone
            ?? $notifiable->student?->phone
            ?? null);

        if ($phoneNumber === null) {
            Log::warning('SMS skipped: no valid phone number found.', [
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

    private function normalizePhoneNumber(mixed $phoneNumber): ?string
    {
        if ($phoneNumber === null) {
            return null;
        }

        $rawPhoneNumber = trim((string) $phoneNumber);
        $digits = preg_replace('/\D+/', '', $rawPhoneNumber);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($rawPhoneNumber, '+')) {
            return $digits;
        }

        if (str_starts_with($digits, '00')) {
            $internationalNumber = substr($digits, 2);
            return $internationalNumber !== '' ? $internationalNumber : null;
        }

        if (str_starts_with($digits, self::DEFAULT_COUNTRY_CODE)) {
            return $digits;
        }

        $localNumber = ltrim($digits, '0');
        return $localNumber !== ''
            ? self::DEFAULT_COUNTRY_CODE . $localNumber
            : null;
    }
}
