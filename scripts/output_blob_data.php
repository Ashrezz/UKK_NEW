<?php
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Peminjaman;

$id = $argv[1] ?? 1;
$r = Peminjaman::withTrashed()->find($id);
if (!$r) {
    echo "Record {$id} not found\n";
    exit(1);
}

if (empty($r->bukti_pembayaran_blob)) {
    echo "Record {$id} has no BLOB\n";
    exit(1);
}

$mime = $r->bukti_pembayaran_mime ?? 'image/png';
$base64 = base64_encode($r->bukti_pembayaran_blob);
echo "data:{$mime};base64,{$base64}\n";
