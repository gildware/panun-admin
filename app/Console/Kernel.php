<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('notifications:send-booking-reminders')->everyFiveMinutes();
        $schedule->command('notifications:send-lead-followup-reminders')->everyFiveMinutes();
        // Home-bundle cache is manual-rebuild only (admin Reset home cache).
        // Do not schedule customer:home-cache:warm — Hostinger keeps last build forever.
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
