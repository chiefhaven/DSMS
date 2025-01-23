<?php

namespace App\Http\Middleware;

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\AttendanceController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfUnauthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            // Get the 'token' parameter from the current route
            $token = $request->route('token');

            // Redirect to the unauthenticated QR scan route
            return redirect()->route('invoiceQrCode', ['token' => $token]);
        }

        return $next($request);
    }
}
