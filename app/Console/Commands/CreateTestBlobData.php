<?php

namespace App\Console\Commands;

use App\Models\Peminjaman;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateTestBlobData extends Command
{
    protected $signature = 'app:create-test-blob-data';
    protected $description = 'Create a test peminjaman record with BLOB image data for testing';

    public function handle(): void
    {
        // Create a minimal 1x1 PNG (base64 encoded)
        $pngData = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
        );

        // Get first admin user
        $adminUser = DB::table('users')->where('role', 'admin')->first();
        $ruangId = DB::table('ruang')->first()?->id ?? 1;

        if (!$adminUser) {
            $this->error('❌ No admin user found in database');
            return;
        }

        // Create test peminjaman with BLOB
        $peminjaman = Peminjaman::create([
            'user_id' => $adminUser->id,
            'ruang_id' => $ruangId,
            'tanggal' => now()->addDay()->format('Y-m-d'),
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '11:00:00',
            'keperluan' => 'Test BLOB Upload',
            'status' => 'approved',
            'status_pembayaran' => 'terverifikasi',
            'biaya' => 50000,
            'bukti_pembayaran_blob' => $pngData,
            'bukti_pembayaran_mime' => 'image/png',
            'bukti_pembayaran_name' => 'test-blob.png',
            'bukti_pembayaran_size' => strlen($pngData),
        ]);

        $this->info("✅ Test BLOB uploaded:");
        $this->info("   - Peminjaman ID: {$peminjaman->id}");
        $this->info("   - BLOB size: " . strlen($pngData) . " bytes");
        $this->info("   - MIME: image/png");
        $this->newLine();
        $this->info("🔗 Test URLs:");
        $this->info("   - http://localhost:8000/pembayaran/bukti/blob/{$peminjaman->id}");
        $this->info("   - http://localhost:8000/pembayaran/debug/blob/{$peminjaman->id}");
        $this->newLine();
        $this->info("✅ Try opening those URLs in your browser to verify BLOB serving!");
    }
}
