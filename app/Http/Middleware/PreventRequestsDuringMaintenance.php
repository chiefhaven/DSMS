<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

class PreventRequestsDuringMaintenance extends Middleware
{
    public function handle($request, Closure $next)
    {
        $user = $request->user() ?? $request->user('sanctum');

        if ($user?->can('manage system')) {
            return $next($request);
        }

        if (config('app.maintenance.enabled')) {
            return response()->view('errors.503', [], 503);
        }

        return parent::handle($request, $next);
    }

    /**
     * The URIs that should be reachable while maintenance mode is enabled.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
