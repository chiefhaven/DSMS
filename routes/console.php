<?php

use App\Http\Controllers\InstructorPaymentController;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pay:early-bonuses', function () {
    // Simulate an authenticated user with the superAdmin role
    $user = \App\Models\User::role('superAdmin')->first();

    if (!$user) {
        $this->error('No superAdmin user found.');
        return;
    }

    Auth::login($user); // Log in the user for the duration of the command

    // Send the request (no input fields needed in this case)
    $request = Request::create('/bonuses/pay-early', 'POST');

    // Call the controller via the route
    $response = app(InstructorPaymentController::class)->store($request);

    $responseData = $response->getData(true);
    $this->info($responseData['message'] ?? 'Done');

    Auth::logout(); // Log the user back out
})->purpose('Trigger early bonus payments for instructors');