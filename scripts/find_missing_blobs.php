<?php
require __DIR__ . '/../bootstrap/app.php';

use App\Models\Peminjaman;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find all records with bukti_pembayaran path but no BLOB
$records = Peminjaman::withTrashed()  // Include soft-deleted
    ->whereNotNull('bukti_pembayaran')
    ->where(function ($q) {
        $q->whereNull('bukti_pembayaran_blob')
          ->orWhere('bukti_pembayaran_blob', '');
    })
    ->get();

echo "\n=== Records with path but no BLOB ===\n\n";

$disk = config('filesystems.default') === 's3' ? 's3' : 'public';
$finfo = new \finfo(FILEINFO_MIME_TYPE);

foreach ($records as $r) {
    $pathHex = $r->bukti_pembayaran;
    
    // Try to decode if it's hex-encoded
    if (strpos($pathHex, '0x') === 0) {
        $path = hex2bin(substr($pathHex, 2));
    } else {
        $path = $pathHex;
    }
    
    echo "ID: {$r->id}\n";
    echo "  Path: {$path}\n";
    echo "  Deleted: " . ($r->deleted_at ? 'YES (' . $r->deleted_at . ')' : 'NO') . "\n";
    
    // Try to find and load file
    $candidates = [
        storage_path('app/public/' . $path),
        storage_path('app/' . $path),
        public_path($path),
        $path,
    ];
    
    $found = false;
    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            echo "  ✓ Found at: {$candidate}\n";
            $contents = file_get_contents($candidate);
            $mime = $finfo->buffer($contents) ?: 'image/jpeg';
            $size = strlen($contents);
            echo "    Size: {$size} bytes, MIME: {$mime}\n";
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        echo "  ✗ File not found in any location\n";
    }
    
    echo "\n";
}
