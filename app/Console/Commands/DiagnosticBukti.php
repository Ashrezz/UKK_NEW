<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Peminjaman;
use Illuminate\Support\Facades\Storage;

class DiagnosticBukti extends Command
{
    protected $signature = 'diagnostic:bukti {--limit=10 : Number of records to show}';

    protected $description = 'Diagnostic tool to check bukti_pembayaran storage status and database entries';

    public function handle(): int
    {
        $limit = $this->option('limit');
        $this->info("Checking bukti_pembayaran entries (limit: {$limit})...\n");

        $records = Peminjaman::whereNotNull('bukti_pembayaran')
            ->latest()
            ->limit($limit)
            ->get();

        if ($records->isEmpty()) {
            $this->warn('No bukti_pembayaran records found in database.');
            return 0;
        }

        $this->line("Found {$records->count()} records:\n");

        foreach ($records as $p) {
            $value = $p->getOriginal('bukti_pembayaran');
            $valueType = gettype($value);

            // Detect if it's binary data
            $isBinary = false;
            if (is_string($value)) {
                $isBinary = @getimagesizefromstring($value) !== false;
            }

            $this->line("ID: {$p->id}");
            $this->line("  Value Type: {$valueType}");

            if (is_string($value)) {
                $len = strlen($value);
                if ($isBinary) {
                    $this->line("  Content: [BINARY IMAGE DATA, {$len} bytes]");
                } else {
                    $this->line("  Content: {$value}");

                    // Check if file exists
                    $candidates = [$value, 'bukti_pembayaran/' . basename($value)];
                    $exists = false;
                    foreach ($candidates as $candidate) {
                        if (Storage::disk('public')->exists($candidate)) {
                            $this->line("  ✓ File exists: {$candidate}");
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $this->line("  ✗ File NOT found in storage");
                    }
                }
            } else {
                $this->line("  Content: [UNEXPECTED DATA TYPE: {$valueType}]");
            }

            $this->line("  Accessor result: " . ($p->bukti_pembayaran_src ?? 'null'));
            $this->line('');
        }

        // Also check filesystem
        $this->line("\n" . str_repeat('=', 60) . "\n");
        $this->info("Checking filesystem: storage/app/public/bukti_pembayaran/\n");

        $path = storage_path('app/public/bukti_pembayaran');
        if (!is_dir($path)) {
            $this->warn("Directory does not exist: {$path}");
            return 0;
        }

        $files = @scandir($path);
        if (!$files) {
            $this->warn("Could not read directory: {$path}");
            return 1;
        }

        $files = array_diff($files, ['.', '..']);
        if (empty($files)) {
            $this->warn("No files found in: {$path}");
            return 0;
        }

        $this->line("Files in directory (" . count($files) . "):\n");
        foreach ($files as $file) {
            $full = "{$path}/{$file}";
            $size = filesize($full);
            $this->line("  - {$file} ({$size} bytes)");
        }

        return 0;
    }
}
