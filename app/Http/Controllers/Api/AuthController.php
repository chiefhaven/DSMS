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
        // Use a more specific rate limit key that includes identifier
        $key = 'login-attempts:' . $request->ip() . ':' . md5($request->login ?? '');

        // Rate limiting - check before validation to prevent resource waste
        if ($rateLimiter->tooManyAttempts($key, 5)) {
            return $this->sendErrorResponse(
                'Too many login attempts. Please try again in ' .
                $rateLimiter->availableIn($key) . ' seconds.',
                429
            );
        }

        // Validation with better field names
        $validator = Validator::make($request->all(), [
            'login' => 'required|string|max:255',
            'password' => 'required|string|min:8', // Increased min length for better security
        ], [
            'login.required' => 'Email or phone number is required.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
        ]);

        if ($validator->fails()) {
            return $this->sendErrorResponse(
                'Validation failed',
                422,
                $validator->errors()
            );
        }

        $loginInput = trim($request->login);
        $user = $this->findUserByLogin($loginInput);

        // Use constant-time comparison for password check
        if ($user && Hash::check($request->password, $user->password)) {
            // Check if user is active/verified
            if (!$this->isUserActive($user)) {
                return $this->sendErrorResponse(
                    'Account is not active or verified.',
                    403
                );
            }

            // Clear rate limiter on successful login
            $rateLimiter->clear($key);

            // Log successful login
            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'ip' => $request->ip()
            ]);

            // Create token with abilities/scopes if needed
            $token = $user->createToken(
                'auth_token_' . $request->userAgent(),
                ['basic-access'] // Add appropriate abilities
            );

            return response()->json([
                'status' => 'success',
                'data' => [
                    'token' => $token->plainTextToken,
                    'token_type' => 'Bearer',
                    'expires_in' => config('sanctum.expiration'),
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ]
                ],
            ], 200);
        }

        // Record failed attempt
        $rateLimiter->hit($key);

        // Log failed attempt
        Log::warning('Failed login attempt', [
            'login' => $loginInput,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // Use generic message to prevent user enumeration
        return $this->sendErrorResponse(
            'The provided credentials are incorrect.',
            401 // Use 401 Unauthorized for failed auth
        );
    }

    /**
     * Find user by email or phone
     */
    private function findUserByLogin($login)
    {
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return User::where('email', $login)->first();
        }

        // Validate phone format if needed
        if ($this->isValidPhoneNumber($login)) {
            $student = Student::where('phone', $login)
                ->with('user')
                ->first();

            return $student ? $student->user : null;
        }

        return null;
    }

    /**
     * Check if phone number is valid
     */
    private function isValidPhoneNumber($phone)
    {
        // Add phone validation logic based on your requirements
        return preg_match('/^[0-9+\-\s()]{10,15}$/', $phone);
    }

    /**
     * Check if user account is active
     */
    private function isUserActive($user)
    {
        // Implement your account status logic
        return !$user->is_banned && $user->email_verified_at !== null;
    }

    /**
     * Send consistent error response
     */
    private function sendErrorResponse($message, $statusCode, $errors = null)
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
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
