<?php

namespace App\Http\Middleware;

use App\Http\Controllers\InvoiceController;
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
        // Check if the user is not authenticated
        if (!Auth::check()) {
            // Retrieve the 'token' parameter from the route
            $token = $request->route('token');

            // Redirect to the 'invoiceQrCode' route, passing the token
            return redirect()->route('docsQrCode', ['token' => $token]);
        }

        // Allow the request to proceed if the user is authenticated
        return $next($request);
    }
}
