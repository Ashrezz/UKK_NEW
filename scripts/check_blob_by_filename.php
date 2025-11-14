<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Peminjaman;

$arg = $argv[1] ?? null;
if (!$arg) {
    echo "Usage: php check_blob_by_filename.php <filename-or-id>\n";
    exit(1);
}

// If arg is numeric, treat as id
if (ctype_digit($arg)) {
    $p = Peminjaman::find($arg);
    if (!$p) { echo "Record with ID {$arg} not found\n"; exit(0); }
} else {
    // otherwise treat as filename or path
    $p = Peminjaman::where('bukti_pembayaran', $arg)
        ->orWhere('bukti_pembayaran', 'like', '%'.basename($arg))
        ->orWhere('bukti_pembayaran_name', basename($arg))
        ->first();
    if (!$p) { echo "No record found for filename: {$arg}\n"; exit(0); }
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

