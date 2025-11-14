<?php

namespace Database\Seeders;

use App\Models\Peminjaman;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PopulateMissingBlobsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔄 Populating missing BLOBs with placeholders...');

        $records = Peminjaman::whereNotNull('bukti_pembayaran')
            ->where(function ($query) {
                $query->whereNull('bukti_pembayaran_blob')
                    ->orWhere('bukti_pembayaran_blob', '');
            })
            ->get();

        $count = $records->count();
        $this->command->info("Found {$count} records without BLOB data.");

        if ($count === 0) {
            $this->command->info('✅ All records already have BLOB data!');
            return;
        }

        $generated = 0;
        $failed = 0;

        foreach ($records as $peminjaman) {
            try {
                $placeholder = $this->generatePlaceholderImage();
                $filename = basename($peminjaman->bukti_pembayaran ?? 'bukti.png');

                $peminjaman->bukti_pembayaran_blob = $placeholder;
                $peminjaman->bukti_pembayaran_mime = 'image/png';
                $peminjaman->bukti_pembayaran_name = $filename;
                $peminjaman->bukti_pembayaran_size = strlen($placeholder);
                $peminjaman->save();

                $this->command->line("  ✅ ID {$peminjaman->id}: {$filename}");
                $generated++;
            } catch (\Throwable $e) {
                $this->command->error("  ❌ ID {$peminjaman->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->command->info('');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info("✅ Generated: {$generated}");
        $this->command->info("❌ Failed: {$failed}");
        $this->command->info('═══════════════════════════════════════');
    }

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
