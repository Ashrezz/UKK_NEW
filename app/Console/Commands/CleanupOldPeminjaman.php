<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Peminjaman;
use Illuminate\Support\Facades\Storage;

class CleanupOldPeminjaman extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'peminjaman:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus otomatis peminjaman yang tanggalnya sudah lewat';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = now()->format('Y-m-d');

        $old = Peminjaman::where('tanggal', '<', $today)->get();
        $count = 0;
        $deletedIds = [];

        foreach ($old as $p) {
            // Soft-delete the record (allow restore)
            try {
                $p->delete();
                $deletedIds[] = $p->id;
                $count++;
            } catch (\Throwable $e) {
                // ignore individual delete errors
            }
        }

        // Log cleanup details to a dedicated log file
        try {
            $logPath = storage_path('logs/peminjaman_cleanup.log');
            $message = '[' . now()->toDateTimeString() . '] Cleanup executed. Soft-deleted count: ' . $count . '. IDs: ' . implode(',', $deletedIds) . PHP_EOL;
            file_put_contents($logPath, $message, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // ignore logging errors
        }

        $this->info("Cleanup complete. Soft-deleted {$count} old peminjaman(s).");
        return Command::SUCCESS;
    }
}
