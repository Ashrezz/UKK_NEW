<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            // Use BINARY (BLOB). If you need larger sizes (MEDIUM/LONG), adjust via DB-specific ALTER
            if (!Schema::hasColumn('peminjaman', 'bukti_pembayaran_blob')) {
                $table->binary('bukti_pembayaran_blob')->nullable();
            }
            if (!Schema::hasColumn('peminjaman', 'bukti_pembayaran_mime')) {
                $table->string('bukti_pembayaran_mime')->nullable();
            }
            if (!Schema::hasColumn('peminjaman', 'bukti_pembayaran_name')) {
                $table->string('bukti_pembayaran_name')->nullable();
            }
            if (!Schema::hasColumn('peminjaman', 'bukti_pembayaran_size')) {
                $table->integer('bukti_pembayaran_size')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            if (Schema::hasColumn('peminjaman', 'bukti_pembayaran_blob')) {
                $table->dropColumn('bukti_pembayaran_blob');
            }
            if (Schema::hasColumn('peminjaman', 'bukti_pembayaran_mime')) {
                $table->dropColumn('bukti_pembayaran_mime');
            }
            if (Schema::hasColumn('peminjaman', 'bukti_pembayaran_name')) {
                $table->dropColumn('bukti_pembayaran_name');
            }
            if (Schema::hasColumn('peminjaman', 'bukti_pembayaran_size')) {
                $table->dropColumn('bukti_pembayaran_size');
            }
        });
    }
};
