<?php

namespace App\Console\Commands;

use App\Models\Peminjaman;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateMissingBlobsFromPath extends Command
{
    protected $signature = 'app:migrate-missing-blobs-from-path {--dry-run}';
    protected $description = 'Migrate records with filesystem path but missing BLOB: find file and copy to BLOB';

    public function handle()
    {
        $this->info('Scanning peminjaman records for missing BLOBs with file paths...');

        // Find all records (including soft-deleted) with path but no BLOB
        $query = Peminjaman::withTrashed()
            ->whereNotNull('bukti_pembayaran')
            ->where(function ($q) {
                $q->whereNull('bukti_pembayaran_blob')
                  ->orWhere('bukti_pembayaran_blob', '');
            });

        $total = $query->count();

        if ($total === 0) {
            $this->info('No records need migration.');
            return 0;
        }

        $this->info("Found {$total} records to migrate.");

        $dry = $this->option('dry-run');
        if ($dry) {
            $this->info('Running in dry-run mode. No changes will be made.');
        }

        $migrated = 0;
        $failed = 0;
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        foreach ($query->cursor() as $r) {
            $pathRaw = $r->bukti_pembayaran;

            // Decode hex if needed
            if (is_string($pathRaw) && strpos($pathRaw, '0x') === 0) {
                $path = hex2bin(substr($pathRaw, 2));
            } else {
                $path = $pathRaw;
            }

            // Try multiple candidate locations
            $candidates = [
                storage_path('app/public/' . $path),
                storage_path('app/' . $path),
                public_path($path),
                $path,
            ];

            // Also try with basename only
            $basename = basename($path);
            $candidates[] = storage_path('app/public/bukti_pembayaran/' . $basename);
            $candidates[] = public_path('bukti_pembayaran/' . $basename);

            $found = false;
            $contents = null;

            foreach ($candidates as $candidate) {
                if (file_exists($candidate)) {
                    try {
                        $contents = file_get_contents($candidate);
                        if ($contents) {
                            $found = true;
                            break;
                        }
                    } catch (\Throwable $e) {
                        continue;
                    }
                }
            }

            if ($found && $contents) {
                try {
                    $mime = $finfo->buffer($contents) ?: 'image/jpeg';
                    $size = strlen($contents);

                    if ($dry) {
                        $this->line('[DRY] ID ' . $r->id . ': would migrate (' . $size . ' bytes, ' . $mime . ')');
                    } else {
                        $r->bukti_pembayaran_blob = $contents;
                        $r->bukti_pembayaran_mime = $mime;
                        $r->bukti_pembayaran_name = $basename;
                        $r->bukti_pembayaran_size = $size;
                        $r->save();

                        $this->line('ID ' . $r->id . ': migrated (' . $size . ' bytes)');
                        $migrated++;
                    }
                } catch (\Throwable $e) {
                    $this->error('ID ' . $r->id . ': failed - ' . $e->getMessage());
                    $failed++;
                }
            } else {
                $this->warn('ID ' . $r->id . ': file not found (' . $path . ')');
                $failed++;
            }
        }

        $this->info('');
        $this->info('Summary: migrated=' . $migrated . ', failed=' . $failed);

        return 0;
    }
}
