<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Peminjaman;
use Illuminate\Support\Facades\Storage;

class MigrateBuktiPembayaran extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:bukti-pembayaran {--dry-run : Show what would be done without writing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate bukti_pembayaran BLOBs to storage/app/public/bukti_pembayaran and update DB to store the path';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting migration of bukti_pembayaran...');

        $items = Peminjaman::whereNotNull('bukti_pembayaran')->get();
        $count = 0;
        foreach ($items as $item) {
            $value = $item->getOriginal('bukti_pembayaran');

            // Skip if value already looks like a path/URL
            if (is_string($value) && preg_match('/^public\/|^bukti_pembayaran\/|^https?:\/\//', $value)) {
                $this->line("[skip] #{$item->id} - already a path or URL: {$value}");
                continue;
            }

            if (!is_string($value)) {
                $this->line("[skip] #{$item->id} - non-string value, cannot process");
                continue;
            }

            // Detect if it's image binary
            $imgInfo = @getimagesizefromstring($value);
            if ($imgInfo === false || !isset($imgInfo['mime'])) {
                $this->line("[skip] #{$item->id} - not detected as image binary");
                continue;
            }

            $mime = $imgInfo['mime'];
            $ext = null;
            switch ($mime) {
                case 'image/jpeg':
                    $ext = 'jpg';
                    break;
                case 'image/png':
                    $ext = 'png';
                    break;
                case 'image/gif':
                    $ext = 'gif';
                    break;
                case 'image/webp':
                    $ext = 'webp';
                    break;
                default:
                    $ext = 'bin';
            }

            $filename = $item->id . '_' . time() . '.' . $ext; // file name only
            $pathInDisk = 'bukti_pembayaran/' . $filename; // path under public disk

            $this->line("[write] #{$item->id} -> {$pathInDisk}");

            if ($this->option('dry-run')) {
                $count++;
                continue;
            }

            // Write file to public disk
            try {
                Storage::disk('public')->put($pathInDisk, $value);

                // Update DB to store the relative path inside the public disk
                $item->update(['bukti_pembayaran' => $pathInDisk]);

                $count++;
            } catch (\Throwable $e) {
                $this->error("Failed to write for #{$item->id}: " . $e->getMessage());
            }
        }

        $this->info("Migration complete. Processed: {$count} items.");
        $this->info('If you stored files, run: php artisan storage:link if you haven\'t created the public link.');

        return 0;
    }
}
