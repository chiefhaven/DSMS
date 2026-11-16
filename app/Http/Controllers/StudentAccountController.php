<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class StudentAccountController extends Controller
{
    public function sendPasswordResetLink(Request $request, Student $student): JsonResponse
    {
        $student = $this->readableStudent($request, $student);
        $user = $student->user;

        if (!$user || !$user->email) {
            return response()->json(['message' => 'No student portal account with an email address is linked.'], 422);
        }

        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (TransportExceptionInterface $exception) {
            Log::warning('Student password reset email delivery failed.', [
                'student_id' => $student->getKey(),
            ]);

            return response()->json([
                'message' => 'The reset email could not be delivered. Verify the mail service settings and try again.',
            ], 502);
        }

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'A password reset link was sent to the student account email.']);
        }

        $statusCode = $status === Password::RESET_THROTTLED ? 429 : 422;

        return response()->json(['message' => __($status)], $statusCode);
    }

    public function generateTemporaryPassword(Request $request, Student $student): JsonResponse
    {
        $student = $this->readableStudent($request, $student);
        $user = $student->user;

        if (!$user) {
            return response()->json(['message' => 'No student portal account is linked.'], 422);
        }

        $password = Str::random(32);

        DB::transaction(function () use ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
                'must_change_password' => true,
            ])->save();

            $user->tokens()->delete();
            Password::broker()->deleteToken($user);
        });

        return response()->json([
            'message' => 'A temporary password was generated. Share it securely; the student must change it after signing in.',
            'temporary_password' => $password,
        ])->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }

    private function readableStudent(Request $request, Student $student): Student
    {
        return Student::readableBy($request->user())->whereKey($student->getKey())->firstOrFail();
    }

}
