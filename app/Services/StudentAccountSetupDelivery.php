<?php

namespace App\Services;

use App\Models\Student;
use App\Notifications\StudentAccountSetup;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use RuntimeException;
use Throwable;

class StudentAccountSetupDelivery
{
    public function send(Student $student): array
    {
        $user = $student->user;
        if (!$user || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            Log::warning('Student account setup could not be sent: account or valid email missing.', ['student_id' => $student->id]);
            return ['email', 'text'];
        }

        try {
            if (!filter_var(config('app.url'), FILTER_VALIDATE_URL)) {
                throw new RuntimeException('Application URL is not configured.');
            }
            $token = Password::broker()->createToken($user);
            $url = rtrim(config('app.url'), '/').route('session.password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], false);
        } catch (Throwable $exception) {
            Log::error('Student account setup token could not be created.', [
                'student_id' => $student->id, 'exception' => get_class($exception),
            ]);
            return ['email', 'text'];
        }

        $failed = [];
        try {
            $transport = config('mail.mailers.'.config('mail.default').'.transport');
            if (in_array($transport, ['log', 'array', 'failover'], true)) {
                throw new RuntimeException('A delivery mail transport must be configured.');
            }
            $user->notify(new StudentAccountSetup($url, (int) config('auth.passwords.users.expire')));
        } catch (Throwable $exception) {
            Log::error('Student account setup email failed.', [
                'student_id' => $student->id, 'exception' => get_class($exception),
            ]);
            $failed[] = 'email';
        }

        try {
            $phone = preg_replace('/\D/', '', $student->phone ?? '');
            if ($phone === '') {
                throw new RuntimeException('Student phone number is missing.');
            }
            if (!str_starts_with($phone, '265')) {
                $phone = '265'.ltrim($phone, '0');
            }
            if (!config('services.smsApi.url') || !config('services.smsApi.token') || !config('services.smsApi.from')) {
                throw new RuntimeException('SMS delivery is not configured.');
            }
            $response = Http::withoutRedirecting()->withHeaders([
                'Authorization' => config('services.smsApi.token'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post(rtrim(config('services.smsApi.url'), '/').'/sms/send', [
                'message' => 'Student portal login: '.$user->email.'. Set your password (expires in '.
                    config('auth.passwords.users.expire').' minutes): '.$url,
                'type' => 'plain',
                'sender_id' => config('services.smsApi.from'),
                'recipient' => $phone,
            ]);
            if (!$response->successful()) {
                throw new RuntimeException('SMS delivery was not accepted.');
            }
        } catch (Throwable $exception) {
            Log::error('Student account setup text failed.', [
                'student_id' => $student->id, 'exception' => get_class($exception),
            ]);
            $failed[] = 'text';
        }

        return $failed;
    }
}
