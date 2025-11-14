<?php
require __DIR__ . '/../bootstrap/app.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Peminjaman;

// Get latest 5 records
$records = Peminjaman::orderBy('id', 'desc')->limit(5)->get();

echo "\n=== Latest 5 Peminjaman Records ===\n\n";

foreach ($records as $r) {
    echo "ID: {$r->id}\n";
    echo "  bukti_pembayaran: " . ($r->bukti_pembayaran ? substr($r->bukti_pembayaran, 0, 50) : 'NULL') . "\n";
    echo "  blob size: " . ($r->bukti_pembayaran_blob ? strlen($r->bukti_pembayaran_blob) . ' bytes' : 'NULL') . "\n";
    echo "  mime: " . ($r->bukti_pembayaran_mime ?? 'NULL') . "\n";
    echo "  name: " . ($r->bukti_pembayaran_name ?? 'NULL') . "\n";
    echo "\n";
}
