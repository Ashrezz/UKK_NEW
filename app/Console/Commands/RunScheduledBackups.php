<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\DatabaseBackupService;
use Carbon\Carbon;

class RunScheduledBackups extends Command
{
    protected $signature = 'app:run-scheduled-backups';
    protected $description = 'Check backup settings and run database backup if due';

    public function handle(DatabaseBackupService $service): int
    {
        $setting = DB::table('backup_settings')->orderByDesc('id')->first();
        if (!$setting || !$setting->enabled) {
            $this->info('No enabled backup setting found.');
            return Command::SUCCESS;
        }
        $now = Carbon::now();
        if ($setting->next_run_at && $now->lt(Carbon::parse($setting->next_run_at))) {
            $this->info('Not time yet for backup. Next run at ' . $setting->next_run_at);
            return Command::SUCCESS;
        }

        $result = $service->generate();
        $this->info('Backup created: ' . $result['filename']);

        // Compute next_run_at
        $interval = max(1, (int)$setting->frequency_interval);
        $unit = $setting->frequency_unit;
        $next = Carbon::now()->setTimeFromTimeString($setting->run_time);
        if ($unit === 'day') {
            $next->addDays($interval);
        } elseif ($unit === 'week') {
            $next->addWeeks($interval);
        } elseif ($unit === 'month') {
            $next->addMonths($interval);
        } else { // year
            $next->addYears($interval);
        }

        DB::table('backup_settings')->where('id', $setting->id)->update([
            'last_run_at' => Carbon::now(),
            'next_run_at' => $next,
        ]);

        return Command::SUCCESS;
    }
}
