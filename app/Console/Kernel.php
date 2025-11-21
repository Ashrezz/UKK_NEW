<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run cleanup of old peminjaman daily at 00:05
        $schedule->command('peminjaman:cleanup')->dailyAt('00:05');
        // Run backup check hourly to see if it's time to execute scheduled backup
        $schedule->command('app:run-scheduled-backups')->hourly();
        // Auto cleanup past bookings every hour (checks date and time)
        $schedule->command('bookings:cleanup')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
