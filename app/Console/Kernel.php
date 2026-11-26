<?php

namespace App\Console;

use App\Jobs\SyncMbiraSyncPositions;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        $schedule->command('staff:auto-end-contracts')->dailyAt('00:10')->timezone('Africa/Harare')
            ->description('Automatically mark staff contracts as ended when end date is reached');

        $schedule->command('fleet:notify-dues')->dailyAt('08:00')->timezone('Africa/Harare')
            ->withoutOverlapping()
            ->description('Notify instructors and permitted administrators about fleet dues');

        $schedule->command('hrm:payroll-status')->monthlyOn(25, '07:30')->timezone('Africa/Harare')
            ->description('Report payroll status for the current month ahead of month-end processing');

        $schedule->command('hrm:payroll-status')->monthlyOn(28, '07:30')->timezone('Africa/Harare')
            ->description('Report payroll status for the current month on month-end payroll day');

        $schedule->command('hrm:leave-pending --days=3')->weekdays()->at('08:00')->timezone('Africa/Harare')
            ->description('Report pending leave requests awaiting HR review');

        $schedule->job(new SyncMbiraSyncPositions)
            ->everyMinute()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
