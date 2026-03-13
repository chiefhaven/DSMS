<?php

namespace App\Http\Controllers\Api;
use Illuminate\Cache\RateLimiter;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificationController;
use App\Models\Student;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    /**
     * Login The User
     * @param Request $request
     * @return User
     */
    public function register (Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails())
        {
            return response(['errors'=>$validator->errors()->all()], 422);
        }
        $request['password']=Hash::make($request['password']);
        $request['remember_token'] = Str::random(10);
        $user = User::create($request->toArray());
        // $token = $user->createToken('Laravel Password Grant Client')->accessToken;
        $token = $user->createToken('auth_token');

        $response = ['token' => $token->plainTextToken];
        return response($response, 200);
    }

    public function login(Request $request, RateLimiter $rateLimiter)
    {
        $key = 'login-attempts:' . $request->ip();

        if ($rateLimiter->tooManyAttempts($key, 5)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Too many login attempts. Please try again later.',
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ], [
            'email.required' => 'The email field is required.',
            'password.required' => 'The password field is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            $token = $user->createToken('auth_token');
            $rateLimiter->clear($key);

            return response()->json([
                'status' => 'success',
                'data' => ['token' => $token->plainTextToken],
            ], 200);
        }

        $rateLimiter->hit($key);
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid email address or password',
        ], 422);
    }

    // Handle Forgot Password Request
    public function forgotPasswordReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['status' => 'success', 'message' => 'Password reset link sent.'])
            : response()->json(['status' => 'error', 'message' => 'Unable to send reset link.'], 500);
    }

    public function otp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phonenumber' => 'required|exists:students,phone',
        ]);

        Log::info("OTP Request received for phone number: {$request->phonenumber}");

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find student
        $student = Student::where('phone', $request->phonenumber)->first();

        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'Student not found'
            ], 404);
        }

        // Get related user
        $user = $student->user;

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User account not found'
            ], 404);
        }

        // Create OTP
        $otp = $user->createOneTimePassword();


        //Send SMS
        $sms = new NotificationController();
        Log::info("Sending OTP to {$student->phone}: {$otp->password}");
        $sms->sendSMS("Your OTP is: {$otp->password}", $student->phone);

        return response()->json([
            'status' => 'success',
            'message' => 'OTP sent successfully'
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phonenumber' => 'required|exists:students,phone',
            'otp' => 'required|string',
        ]);

        $user = Student::where('phone', $request->phonenumber)->first()->user;

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        try {
            $result = $user->consumeOneTimePassword($request->otp);

            if ($result) {
                // OTP is valid, user can proceed
                return response()->json(['status' => 'success', 'message' => 'OTP verified successfully.']);
            } else {
                // OTP invalid or expired
                return response()->json(['status' => 'error', 'message' => 'OTP is invalid or expired.'], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to verify OTP.'], 500);
        }
    }

    // Handle Password Reset
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phonenumber' => 'required|exists:students,phone',
            'password' => 'required|string|min:6|confirmed', // expects password_confirmation in request
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get the user linked to the student
        $student = Student::where('phone', $request->phonenumber)->first();

        if (!$student || !$student->user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        $user = $student->user;

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset successful.'
        ]);
    }

}
