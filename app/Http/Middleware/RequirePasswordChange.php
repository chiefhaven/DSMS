<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? $request->user('sanctum');

        if (!$user || !$user->must_change_password) {
            return $next($request);
        }

        if ($request->routeIs(
            'students.password.force-change',
            'students.password.force-change.update',
            'logout'
        )) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'A password change is required before continuing.'], 423);
        }

        return redirect()->route('students.password.force-change');
    }
}
