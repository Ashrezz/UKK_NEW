<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Peminjaman;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateBuktiToBlob extends Command
{
    protected $signature = 'app:migrate-bukti-to-blob {--force : Jalankan tanpa konfirmasi}';
    protected $description = 'Migrasi semua bukti_pembayaran dari file storage ke BLOB database untuk persistence di Railway';

    public function handle()
    {
        $this->info('🔄 Starting migration of bukti_pembayaran to BLOB...');
        $this->newLine();

        // Cek berapa banyak record yang perlu dimigrasikan
        $totalRecords = Peminjaman::whereNotNull('bukti_pembayaran')
            ->where(function ($query) {
                $query->whereNull('bukti_pembayaran_blob')
                    ->orWhere(function ($q) {
                        $q->whereRaw("LENGTH(bukti_pembayaran_blob) = 0 OR bukti_pembayaran_blob = ''");
                    });
            })
            ->count();

        $this->line("📊 Found <fg=cyan>{$totalRecords}</> records need migration");

        if ($totalRecords === 0) {
            $this->info('✅ All records already migrated!');
            return 0;
        }

        $this->line('');
        $this->warn('⚠️  WARNING: Proses ini akan membaca semua gambar ke memory');
        $this->warn('    Untuk file besar/banyak, bisa memakan waktu');
        $this->line('');

        if (!$this->option('force')) {
            if (!$this->confirm("Lanjutkan migrasi {$totalRecords} records?")) {
                $this->info('Dibatalkan.');
                return 1;
            }
        }

        $this->newLine();

        // Dapatkan configured disk
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        $this->line("📁 Using disk: <fg=blue>{$disk}</>");
        $this->newLine();

        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        $migrated = 0;
        $failed = 0;
        $skipped = 0;

        foreach (Peminjaman::whereNotNull('bukti_pembayaran')->cursor() as $peminjaman) {
            try {
                // Skip jika sudah punya BLOB yang valid
                if (!empty($peminjaman->bukti_pembayaran_blob) && strlen($peminjaman->bukti_pembayaran_blob) > 0) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $filepath = $peminjaman->bukti_pembayaran;
                if (!$filepath) {
                    $bar->advance();
                    continue;
                }

                // Normalize path
                if (strpos($filepath, 'public/') === 0) {
                    $filepath = substr($filepath, 7);
                }

                // Coba beberapa lokasi file
                $candidates = [
                    $filepath,
                    'bukti_pembayaran/' . basename($filepath),
                    basename($filepath),
                ];

                $fileContents = null;
                $mime = null;

                // Coba dari disk yang dikonfigurasi
                foreach ($candidates as $candidate) {
                    try {
                        if (Storage::disk($disk)->exists($candidate)) {
                            $fileContents = Storage::disk($disk)->get($candidate);
                            // Deteksi MIME type
                            $finfo = new \finfo(FILEINFO_MIME_TYPE);
                            $mime = $finfo->buffer($fileContents) ?: 'image/jpeg';
                            break;
                        }
                    } catch (Throwable $e) {
                        // continue
                    }
                }

                // Jika tidak ketemu di configured disk, coba disk lain
                if (!$fileContents) {
                    $alternateDisk = $disk === 's3' ? 'public' : 's3';
                    foreach ($candidates as $candidate) {
                        try {
                            if (Storage::disk($alternateDisk)->exists($candidate)) {
                                $fileContents = Storage::disk($alternateDisk)->get($candidate);
                                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                                $mime = $finfo->buffer($fileContents) ?: 'image/jpeg';
                                break;
                            }
                        } catch (Throwable $e) {
                            // continue
                        }
                    }
                }

                // Jika masih tidak ketemu, coba dari storage_path
                if (!$fileContents) {
                    $fullPath = storage_path('app/public/bukti_pembayaran/' . basename($filepath));
                    if (file_exists($fullPath)) {
                        $fileContents = file_get_contents($fullPath);
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $mime = $finfo->buffer($fileContents) ?: 'image/jpeg';
                    }
                }

                // Jika masih tidak ketemu, coba dari public_path
                if (!$fileContents) {
                    $publicPath = public_path('bukti_pembayaran/' . basename($filepath));
                    if (file_exists($publicPath)) {
                        $fileContents = file_get_contents($publicPath);
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $mime = $finfo->buffer($fileContents) ?: 'image/jpeg';
                    }
                }

                if ($fileContents) {
                    // Simpan ke BLOB
                    $peminjaman->bukti_pembayaran_blob = $fileContents;
                    $peminjaman->bukti_pembayaran_mime = $mime ?? 'image/jpeg';
                    $peminjaman->bukti_pembayaran_name = basename($filepath);
                    $peminjaman->bukti_pembayaran_size = strlen($fileContents);
                    $peminjaman->save();
                    $migrated++;
                } else {
                    $failed++;
                }
            } catch (Throwable $e) {
                $failed++;
                \Log::error("Failed to migrate bukti_pembayaran for peminjaman {$peminjaman->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->line("✅ Migration completed!");
        $this->line("   ✓ Migrated: <fg=green>{$migrated}</>");
        $this->line("   ⊘ Skipped: <fg=yellow>{$skipped}</>");
        $this->line("   ✗ Failed: <fg=red>{$failed}</>");
        $this->newLine();

        if ($failed > 0) {
            $this->warn("⚠️  {$failed} records failed to migrate. Check logs for details.");
            $this->info('💡 Tip: Untuk file yang missing, upload kembali via web UI');
        } else {
            $this->info('🎉 Semua file berhasil dimigrasikan ke BLOB!');
            $this->info('💡 Tip: Gambar sekarang akan ditampilkan dari database, bahkan di Railway!');
        }

        return 0;
    }
}
