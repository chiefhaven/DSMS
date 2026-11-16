<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForcedPasswordChangeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (!$request->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.force-password-change');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => Str::random(60),
            'must_change_password' => false,
        ])->save();

        event(new PasswordReset($user));

        return redirect()->route('dashboard')->with('status', 'Your password has been updated.');
    }
}
