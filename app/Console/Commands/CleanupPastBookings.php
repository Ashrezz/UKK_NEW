<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Peminjaman;
use Carbon\Carbon;

class CleanupPastBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically delete bookings that have passed their date and time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $today = $now->format('Y-m-d');
        $currentTime = $now->format('H:i');

        // Delete bookings where:
        // 1. Date is before today, OR
        // 2. Date is today but end time has passed
        $deletedCount = 0;

        // First: Delete all bookings with past dates
        $pastDateBookings = Peminjaman::where('tanggal', '<', $today)->get();
        foreach ($pastDateBookings as $booking) {
            $booking->delete();
            $deletedCount++;
        }

        // Second: Delete bookings for today where end time has passed
        $todayBookings = Peminjaman::where('tanggal', '=', $today)->get();
        foreach ($todayBookings as $booking) {
            if ($booking->jam_selesai < $currentTime) {
                $booking->delete();
                $deletedCount++;
            }
        }

        // Log the cleanup
        $logPath = storage_path('logs/auto_cleanup.log');
        $message = '[' . $now->toDateTimeString() . '] Auto cleanup completed. Deleted: ' . $deletedCount . ' bookings.' . PHP_EOL;

        try {
            file_put_contents($logPath, $message, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // Ignore logging errors
        }

        $this->info("Cleanup completed! Deleted {$deletedCount} past bookings.");

        return Command::SUCCESS;
    }
}
