<?php

namespace App\Console\Commands;

use App\Models\Peminjaman;
use Illuminate\Console\Command;

class FillMissingBlobsWithPlaceholder extends Command
{
    protected $signature = 'app:fill-missing-blobs-placeholder {--force}';
    protected $description = 'Fill all peminjaman records missing BLOB with placeholder images (for recovery)';

    public function handle()
    {
        $this->info('Scanning peminjaman records for missing BLOBs...');

        // Find all records (including soft-deleted) that have a path but no BLOB
        $query = Peminjaman::withTrashed()
            ->whereNotNull('bukti_pembayaran')
            ->where(function ($q) {
                $q->whereNull('bukti_pembayaran_blob')
                  ->orWhere('bukti_pembayaran_blob', '');
            });

        $total = $query->count();

        if ($total === 0) {
            $this->info('No records need filling.');
            return 0;
        }

        $this->info("Found {$total} records to fill with placeholder.");

        if (!$this->option('force')) {
            if (!$this->confirm("Fill {$total} records with placeholder BLOBs?")) {
                $this->info('Cancelled.');
                return 1;
            }
        }

        $filled = 0;
        $failed = 0;

        foreach ($query->cursor() as $r) {
            try {
                $pathRaw = $r->bukti_pembayaran;

                // Decode hex if needed
                if (is_string($pathRaw) && strpos($pathRaw, '0x') === 0) {
                    $path = hex2bin(substr($pathRaw, 2));
                } else {
                    $path = $pathRaw;
                }

                $basename = basename($path);

                // Generate placeholder
                $placeholder = $this->generatePlaceholderImage();

                $r->bukti_pembayaran_blob = $placeholder;
                $r->bukti_pembayaran_mime = 'image/png';
                $r->bukti_pembayaran_name = $basename;
                $r->bukti_pembayaran_size = strlen($placeholder);
                $r->save();

                $this->line('ID ' . $r->id . ': filled with placeholder (' . $basename . ')');
                $filled++;
            } catch (\Throwable $e) {
                $this->error('ID ' . $r->id . ': failed - ' . $e->getMessage());
                $failed++;
            }
        }

        $this->info('');
        $this->info('Summary: filled=' . $filled . ', failed=' . $failed);

        return 0;
    }

    private function generatePlaceholderImage()
    {
        $image = imagecreatetruecolor(300, 200);

        $bgColor = imagecolorallocate($image, 220, 220, 220);
        $textColor = imagecolorallocate($image, 80, 80, 80);
        $borderColor = imagecolorallocate($image, 150, 150, 150);

        imagefilledrectangle($image, 0, 0, 300, 200, $bgColor);
        imagerectangle($image, 0, 0, 299, 199, $borderColor);

        // Draw text
        $fontFile = __DIR__ . '/../../resources/fonts/arial.ttf';
        if (!file_exists($fontFile)) {
            // Fallback to built-in font
            imagestring($image, 3, 80, 85, 'Image Not Available', $textColor);
            imagestring($image, 2, 100, 110, '(File Lost)', $textColor);
        } else {
            imagettftext($image, 16, 0, 50, 100, $textColor, $fontFile, 'Image Not Available');
            imagettftext($image, 12, 0, 70, 135, $textColor, $fontFile, '(File Lost)');
        }

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return $imageData;
    }
}
