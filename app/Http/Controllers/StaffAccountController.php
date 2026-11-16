<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStaffAccountRequest;
use App\Models\Administrator;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class StaffAccountController extends Controller
{
    public function updateInstructor(UpdateStaffAccountRequest $request, Instructor $instructor)
    {
        return $this->updateAccount($request, $instructor->user()->firstOrFail());
    }

    public function updateAdministrator(UpdateStaffAccountRequest $request, Administrator $administrator)
    {
        return $this->updateAccount($request, $administrator->user()->firstOrFail());
    }

    public function sendAdministratorPasswordResetLink(Administrator $administrator): JsonResponse
    {
        return $this->sendStaffPasswordResetLink($administrator->user, 'administrator', $administrator->getKey());
    }

    public function generateAdministratorTemporaryPassword(Administrator $administrator): JsonResponse
    {
        return $this->generateStaffTemporaryPassword($administrator->user, 'administrator');
    }

    public function sendInstructorPasswordResetLink(Instructor $instructor): JsonResponse
    {
        return $this->sendStaffPasswordResetLink($instructor->user, 'instructor', $instructor->getKey());
    }

    public function generateInstructorTemporaryPassword(Instructor $instructor): JsonResponse
    {
        return $this->generateStaffTemporaryPassword($instructor->user, 'instructor');
    }

    private function sendStaffPasswordResetLink(?User $user, string $profileLabel, $profileId): JsonResponse
    {
        if (!$user || !$user->email) {
            return response()->json(['message' => "No {$profileLabel} account with an email address is linked."], 422);
        }

        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (TransportExceptionInterface $exception) {
            Log::warning(ucfirst($profileLabel) . ' password reset email delivery failed.', [
                "{$profileLabel}_id" => $profileId,
            ]);

            return response()->json([
                'message' => 'The reset email could not be delivered. Verify the mail service settings and try again.',
            ], 502);
        }

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => "A password reset link was sent to the {$profileLabel} account email."]);
        }

        $statusCode = $status === Password::RESET_THROTTLED ? 429 : 422;

        return response()->json(['message' => __($status)], $statusCode);
    }

    private function generateStaffTemporaryPassword(?User $user, string $profileLabel): JsonResponse
    {
        if (!$user) {
            return response()->json(['message' => "No {$profileLabel} account is linked."], 422);
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
            'message' => "A temporary password was generated. Share it securely; the {$profileLabel} must change it after signing in.",
            'temporary_password' => $password,
        ])->header('Cache-Control', 'no-store, private')
            ->header('Pragma', 'no-cache');
    }

    private function updateAccount(UpdateStaffAccountRequest $request, User $user)
    {
        $data = $request->validated();

        if ($user->is($request->user()) && !$request->boolean('is_active')) {
            throw ValidationException::withMessages(['is_active' => 'You cannot deactivate your own account.']);
        }

        DB::transaction(function () use ($user, $data) {
            if ($user->email !== $data['email']) {
                $user->email_verified_at = null;
            }
            $user->email = $data['email'];
            $user->is_active = (bool) $data['is_active'];
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            if (!$user->is_active || !empty($data['password'])) {
                $user->remember_token = Str::random(60);
                $user->tokens()->delete();
            }
            $user->save();
        });

        return response()->json(['message' => 'Account updated successfully.']);
    }
}
