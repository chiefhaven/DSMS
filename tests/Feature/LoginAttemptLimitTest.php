<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LoginAttemptLimitTest extends TestCase
{
    public function test_web_login_locks_out_after_four_failed_attempts(): void
    {
        $request = LoginRequest::create('/login', 'POST', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);
        $request->setContainer($this->app);

        Auth::shouldReceive('attempt')->times(4)->andReturnFalse();

        for ($attempt = 0; $attempt < 4; $attempt++) {
            try {
                $request->authenticate();
                $this->fail('Invalid credentials should fail authentication.');
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $this->assertArrayHasKey('email', $exception->errors());
            }
        }

        $this->assertSame(4, app(LoginController::class)->maxAttempts());

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $request->ensureIsNotRateLimited();
    }

    public function test_api_login_locks_out_after_four_failed_attempts(): void
    {
        $request = request()->create('/api/login', 'POST', [
            'login' => 'user@example.com',
        ]);
        $key = 'login-attempts:' . $request->ip() . ':' . md5($request->login);
        $rateLimiter = app(RateLimiter::class);

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $rateLimiter->hit($key);
        }

        $response = app(AuthController::class)->login($request, $rateLimiter);

        $this->assertSame(429, $response->getStatusCode());
    }
}
