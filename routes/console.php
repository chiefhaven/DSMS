<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Spatie\OneTimePasswords\Models\OneTimePassword;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SyncMbiraSyncPositions;


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

Schedule::command('model:prune', [
    '--model' => [OneTimePassword::class],
])->daily();

Schedule::job(new SyncMbiraSyncPositions)->everyMinute();
