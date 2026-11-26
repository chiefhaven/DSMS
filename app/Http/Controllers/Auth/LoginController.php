<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    protected $maxAttempts = 4;

    protected function credentials(Request $request)
    {
        return [...$request->only($this->username(), 'password'), 'is_active' => true];
    }

    protected function authenticated(Request $request, $user)
    {
        $instructor = $user->instructor;

        if (
            $user->hasRole('instructor')
            && strtolower((string) $instructor?->department?->name) === 'practical'
            && $instructor?->fleet
        ) {
            $request->session()->put('open_vehicle_update_modal_after_login', true);
        }
    }

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
}
