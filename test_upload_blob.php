<?php
/**
 * Test script: Upload a test image as BLOB to peminjaman record
 * Run: php test_upload_blob.php
 */

require 'bootstrap/app.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Bootstrap the app
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Peminjaman;
use Illuminate\Support\Facades\DB;

// Create a simple 1x1 PNG test image (smallest valid PNG)
$pngData = base64_decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
);

// Get first admin user
$adminUser = DB::table('users')->where('role', 'admin')->first();
$ruangId = DB::table('ruang')->first()?->id ?? 1;

if (!$adminUser) {
    echo "❌ No admin user found\n";
    exit(1);
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

echo "✅ Test BLOB uploaded:\n";
echo "   - Peminjaman ID: {$peminjaman->id}\n";
echo "   - BLOB size: " . strlen($pngData) . " bytes\n";
echo "   - MIME: image/png\n";
echo "\n🔗 Test URLs:\n";
echo "   - http://localhost:8000/pembayaran/bukti/blob/{$peminjaman->id}\n";
echo "   - http://localhost:8000/pembayaran/debug/blob/{$peminjaman->id}\n";
echo "\n✅ Try opening those URLs in your browser to verify BLOB serving!\n";
