<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ExpenseController;

class RoleDispatcherController extends Controller
{
    public function handle(Request $request, $token)
    {
        $user = Auth::user();

        if ($user->hasRole('instructor')) {
            return app(AttendanceController::class)->create($request, $token);
        }

        if ($user->hasRole(['financeAdmin', 'superAdmin', 'admin'])) {
            return app(ExpenseController::class)->studentExpenses($request, $token);
        }

        abort(403, 'Unauthorized.');
    }
}
