<?php

namespace App\Console\Commands;

use App\Models\Peminjaman;
use Illuminate\Console\Command;

class NormalizeBlobRecords extends Command
{
    protected $signature = 'app:normalize-blob-records {--dry-run}';
    protected $description = 'Normalize peminjaman records where images are stored as BLOBs: fill missing metadata and clear filesystem path';

    public function handle()
    {
        $this->info('Scanning peminjaman records for BLOB normalization...');

        $query = Peminjaman::whereNotNull('bukti_pembayaran_blob')
            ->where(function ($q) {
                $q->whereNull('bukti_pembayaran_name')
                  ->orWhereNull('bukti_pembayaran_mime')
                  ->orWhereNull('bukti_pembayaran_size')
                  ->orWhereNotNull('bukti_pembayaran');
            });

        $total = $query->count();

        if ($total === 0) {
            $this->info('No records need normalization.');
            return 0;
        }

        $this->info("Found {$total} records to inspect.");

        $dry = $this->option('dry-run');
        if ($dry) {
            $this->info('Running in dry-run mode. No changes will be made.');
        }

        $modified = 0;
        $skipped = 0;

        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        foreach ($query->cursor() as $r) {
            $needsSave = false;
            $reasons = [];

            $blob = $r->bukti_pembayaran_blob;
            if (empty($blob)) {
                $skipped++;
                continue;
            }

            // Fill mime
            if (empty($r->bukti_pembayaran_mime)) {
                try {
                    $mime = $finfo->buffer($blob) ?: 'application/octet-stream';
                } catch (\Throwable $e) {
                    $mime = 'application/octet-stream';
                }
                $r->bukti_pembayaran_mime = $mime;
                $needsSave = true;
                $reasons[] = 'set_mime';
            }

            // Fill size
            if (empty($r->bukti_pembayaran_size) || $r->bukti_pembayaran_size == 0) {
                $r->bukti_pembayaran_size = strlen($blob);
                $needsSave = true;
                $reasons[] = 'set_size';
            }

            // Fill name
            if (empty($r->bukti_pembayaran_name)) {
                // try to derive from existing bukti_pembayaran path
                if (!empty($r->bukti_pembayaran)) {
                    $r->bukti_pembayaran_name = basename($r->bukti_pembayaran);
                } else {
                    $ext = $this->guessExtensionFromMime($r->bukti_pembayaran_mime ?? 'image/png');
                    $r->bukti_pembayaran_name = 'blob_' . $r->id . ($ext ? '.' . $ext : '');
                }
                $needsSave = true;
                $reasons[] = 'set_name';
            }

            // If a filesystem path exists, clear it (we want BLOB to be primary)
            if (!empty($r->bukti_pembayaran)) {
                $r->bukti_pembayaran = null;
                $needsSave = true;
                $reasons[] = 'cleared_path';
            }

            if ($needsSave) {
                if ($dry) {
                    $this->line('[DRY] ID ' . $r->id . ': would update (' . implode(', ', $reasons) . ')');
                } else {
                    try {
                        $r->save();
                        $this->line('ID ' . $r->id . ': updated (' . implode(', ', $reasons) . ')');
                        $modified++;
                    } catch (\Throwable $e) {
                        $this->error('ID ' . $r->id . ': failed to save - ' . $e->getMessage());
                    }
                }
            } else {
                $skipped++;
            }
        }

        $this->info('');
        $this->info("Summary: inspected={$total}, modified={$modified}, skipped={$skipped}");

        // Update todo list: mark first task completed
        return 0;
    }

    private function guessExtensionFromMime($mime)
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $map[$mime] ?? null;
    }
}
