<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Peminjaman;
use App\Observers\PeminjamanObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Peminjaman observer for priority auto-recalculation
        Peminjaman::observe(PeminjamanObserver::class);

        // Auto-populate missing BLOBs on every request
        // This ensures that any new peminjaman records get placeholder images immediately
        $this->autoPopulateMissingBlobs();
    }

    /**
     * Auto-populate missing BLOBs with placeholder images
     * Called once per request to ensure consistency
     */
    private function autoPopulateMissingBlobs(): void
    {
        try {
            // Only run on web requests, not on console or API
            if (app()->runningInConsole()) {
                return;
            }

            // Check if there are any missing BLOBs
            $missingCount = Peminjaman::whereNotNull('bukti_pembayaran')
                ->where(function ($query) {
                    $query->whereNull('bukti_pembayaran_blob')
                        ->orWhere('bukti_pembayaran_blob', '');
                })
                ->count();

            if ($missingCount === 0) {
                return;
            }

            // Populate missing BLOBs
            $records = Peminjaman::whereNotNull('bukti_pembayaran')
                ->where(function ($query) {
                    $query->whereNull('bukti_pembayaran_blob')
                        ->orWhere('bukti_pembayaran_blob', '');
                })
                ->get();

            foreach ($records as $peminjaman) {
                if (!empty($peminjaman->bukti_pembayaran_blob)) {
                    continue; // Skip if already has BLOB
                }

                try {
                    $placeholder = $this->generatePlaceholderImage();
                    $filename = basename($peminjaman->bukti_pembayaran ?? 'bukti.png');

                    $peminjaman->bukti_pembayaran_blob = $placeholder;
                    $peminjaman->bukti_pembayaran_mime = 'image/png';
                    $peminjaman->bukti_pembayaran_name = $filename;
                    $peminjaman->bukti_pembayaran_size = strlen($placeholder);
                    $peminjaman->save();

                    \Log::info("Auto-populated BLOB for peminjaman ID {$peminjaman->id}");
                } catch (\Throwable $e) {
                    \Log::warning("Failed to auto-populate BLOB for ID {$peminjaman->id}: " . $e->getMessage());
                }
            }
        } catch (\Throwable $e) {
            // Silently fail - don't break the app if auto-populate fails
            \Log::warning("Auto-populate missing BLOBs failed: " . $e->getMessage());
        }
    }

    /**
     * Generate a simple placeholder image (200x200 PNG with text)
     */
    private function generatePlaceholderImage()
    {
        $image = imagecreatetruecolor(200, 200);

        $bgColor = imagecolorallocate($image, 200, 200, 200);
        $textColor = imagecolorallocate($image, 100, 100, 100);
        $borderColor = imagecolorallocate($image, 150, 150, 150);

        imagefilledrectangle($image, 0, 0, 200, 200, $bgColor);
        imagerectangle($image, 0, 0, 199, 199, $borderColor);

        imagestring($image, 2, 50, 90, "No Image", $textColor);
        imagestring($image, 2, 55, 105, "Provided", $textColor);

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return $imageData;
    }
}
