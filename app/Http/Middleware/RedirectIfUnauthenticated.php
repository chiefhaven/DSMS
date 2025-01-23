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
            // Pass the 'id' or 'token' parameter to the redirection action
            $id = $request->route('id') ?? $request->route('token'); // Adjust based on your parameter name
            return redirect()->action([InvoiceController::class, 'unauthenticatedQrScan'], ['id' => $id]);
        }

        return $next($request);
    }
}
