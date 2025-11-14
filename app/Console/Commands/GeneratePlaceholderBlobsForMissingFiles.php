<?php

namespace App\Console\Commands;

use App\Models\Peminjaman;
use Illuminate\Console\Command;

class GeneratePlaceholderBlobsForMissingFiles extends Command
{
    protected $signature = 'app:generate-placeholder-blobs {--force}';
    protected $description = 'Generate placeholder images as BLOB for peminjaman records that have no BLOB data';

    public function handle()
    {
        $this->info('🔄 Scanning peminjaman records for missing BLOBs...');

        // Find all peminjaman records that have a bukti_pembayaran path but NO blob content
        $records = Peminjaman::whereNotNull('bukti_pembayaran')
            ->where(function ($query) {
                $query->whereNull('bukti_pembayaran_blob')
                    ->orWhere('bukti_pembayaran_blob', '');
            })
            ->get();

        $count = $records->count();
        $this->info("Found {$count} records without BLOB data.");

        if ($count === 0) {
            $this->info('✅ All records already have BLOB data!');
            return 0;
        }

        if (!$this->option('force')) {
            if (!$this->confirm("Generate placeholder images for {$count} records?")) {
                $this->info('Cancelled.');
                return 1;
            }
        }

        $generated = 0;
        $failed = 0;

        foreach ($records as $peminjaman) {
            try {
                // Generate a simple placeholder PNG image (1x1 transparent pixel as fallback, or a larger colored image)
                $placeholder = $this->generatePlaceholderImage();
                
                $filename = basename($peminjaman->bukti_pembayaran ?? 'bukti.png');
                
                $peminjaman->bukti_pembayaran_blob = $placeholder;
                $peminjaman->bukti_pembayaran_mime = 'image/png';
                $peminjaman->bukti_pembayaran_name = $filename;
                $peminjaman->bukti_pembayaran_size = strlen($placeholder);
                $peminjaman->save();

                $this->line("  ✅ ID {$peminjaman->id}: Generated placeholder ({$filename})");
                $generated++;
            } catch (\Throwable $e) {
                $this->error("  ❌ ID {$peminjaman->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info('');
        $this->info("═══════════════════════════════════════");
        $this->info("✅ Generated: {$generated}");
        $this->info("❌ Failed: {$failed}");
        $this->info("═══════════════════════════════════════");

        return 0;
    }

    /**
     * Generate a simple placeholder image (200x200 PNG with text)
     */
    private function generatePlaceholderImage()
    {
        // Create a GD image resource
        $image = imagecreatetruecolor(200, 200);
        
        // Colors
        $bgColor = imagecolorallocate($image, 200, 200, 200);      // Light gray background
        $textColor = imagecolorallocate($image, 100, 100, 100);    // Dark gray text
        $borderColor = imagecolorallocate($image, 150, 150, 150);  // Medium gray border

        // Fill background
        imagefilledrectangle($image, 0, 0, 200, 200, $bgColor);

        // Draw border
        imagerectangle($image, 0, 0, 199, 199, $borderColor);

        // Add text
        $fontPath = __DIR__ . '/../../resources/fonts/';
        $text = "No Image\nProvided";
        
        // Use simple text (built-in font 2)
        imagestring($image, 2, 50, 90, "No Image", $textColor);
        imagestring($image, 2, 55, 105, "Provided", $textColor);

        // Convert to PNG buffer
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return $imageData;
    }
}
