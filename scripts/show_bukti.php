<?php
// Quick script to dump peminjaman.bukti_pembayaran values (first 20)
$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';
$app = require_once $projectRoot . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Peminjaman;

$items = Peminjaman::orderBy('id')->limit(20)->get();
foreach ($items as $r) {
    echo "#{$r->id} => DB:";
    $dbval = $r->getOriginal('bukti_pembayaran');
    if ($dbval === null) {
        echo "<NULL>";
    } else {
        $str = $dbval;
        $len = strlen($str);
        if ($len > 200) $str = substr($str,0,200) . '...';
        echo $str;
    }

    echo " | SRC:" . ($r->bukti_pembayaran_src ?? '<null>') . "\n";
}
