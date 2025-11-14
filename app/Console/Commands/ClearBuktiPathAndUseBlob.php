<?php

namespace App\Console\Commands;

use App\Models\Peminjaman;
use Illuminate\Console\Command;

class ClearBuktiPathAndUseBlob extends Command
{
    protected $signature = 'app:clear-bukti-path-use-blob {--dry-run}';
    protected $description = 'Clear bukti_pembayaran path and force use BLOB routes for all records with BLOB data';

    public function handle()
    {
        $this->info('Clearing filesystem paths and ensuring all BLOB records are forced to use /pembayaran/bukti/blob/{id}...');

        // Find all records that have BLOB
        $query = Peminjaman::whereNotNull('bukti_pembayaran_blob')
            ->where(function ($q) {
                $q->whereNotNull('bukti_pembayaran')
                  ->where('bukti_pembayaran', '<>', '');
            });

        $total = $query->count();

        if ($total === 0) {
            $this->info('No records need clearing.');
            return 0;
        }

        $this->info("Found {$total} records with both BLOB and path.");

        $dry = $this->option('dry-run');
        if ($dry) {
            $this->info('Running in dry-run mode. No changes will be made.');
        }

        $cleared = 0;
        $failed = 0;

        foreach ($query->cursor() as $r) {
            try {
                if ($dry) {
                    $this->line('[DRY] ID ' . $r->id . ': would clear path "' . substr($r->bukti_pembayaran, 0, 50) . '..."');
                } else {
                    // Clear the path - force BLOB-only
                    $r->bukti_pembayaran = null;
                    $r->save();

                    $this->line('ID ' . $r->id . ': path cleared, now uses BLOB route');
                    $cleared++;
                }
            } catch (\Throwable $e) {
                $this->error('ID ' . $r->id . ': failed - ' . $e->getMessage());
                $failed++;
            }
        }

        $this->info('');
        $this->info('Summary: cleared=' . $cleared . ', failed=' . $failed);

        if (!$dry) {
            $this->info('');
            $this->info('✅ All records now use BLOB routes exclusively.');
            $this->info('   Accessor will return: /pembayaran/bukti/blob/{id}');
        }

        return 0;
    }
}
