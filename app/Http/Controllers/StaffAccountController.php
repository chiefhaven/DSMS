<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateStaffAccountRequest;
use App\Models\Administrator;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

    private function updateAccount(UpdateStaffAccountRequest $request, User $user)
    {
        $data = $request->validated();

        if ($user->is($request->user()) && !$request->boolean('is_active')) {
            throw ValidationException::withMessages(['is_active' => 'You cannot deactivate your own account.']);
        }

        DB::transaction(function () use ($user, $data) {
            $user->name = $data['name'];
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
