<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Peminjaman;

$id = $argv[1] ?? null;
if ($id) {
    $p = Peminjaman::find($id);
    if (!$p) {
        echo "Record with ID {$id} not found\n";
        exit(0);
    }
    $orig = $p->getOriginal('bukti_pembayaran');
    echo "ID: {$p->id}\n";
    echo "bukti_pembayaran (col): " . ($orig ?? 'NULL') . "\n";
    $hasBlob = !empty($p->bukti_pembayaran_blob) || $p->bukti_pembayaran_blob === "\0";
    if ($hasBlob) {
        echo "blob present: YES (" . strlen($p->bukti_pembayaran_blob) . " bytes)\n";
        echo "mime: " . ($p->bukti_pembayaran_mime ?? 'NULL') . "\n";
        echo "name: " . ($p->bukti_pembayaran_name ?? 'NULL') . "\n";
    } else {
        echo "blob present: NO\n";
    }
    exit(0);
}

// else, search by filename given as argument string
$filename = $argv[1] ?? null;
if ($filename) {
    $p = Peminjaman::where('bukti_pembayaran', $filename)->first();
    if (!$p) {
        echo "No record found for filename: {$filename}\n";
        exit(0);
    }
    $orig = $p->getOriginal('bukti_pembayaran');
    echo "ID: {$p->id}\n";
    echo "bukti_pembayaran (col): " . ($orig ?? 'NULL') . "\n";
    $hasBlob = !empty($p->bukti_pembayaran_blob) || $p->bukti_pembayaran_blob === "\0";
    if ($hasBlob) {
        echo "blob present: YES (" . strlen($p->bukti_pembayaran_blob) . " bytes)\n";
        echo "mime: " . ($p->bukti_pembayaran_mime ?? 'NULL') . "\n";
        echo "name: " . ($p->bukti_pembayaran_name ?? 'NULL') . "\n";
    } else {
        echo "blob present: NO\n";
    }
}
