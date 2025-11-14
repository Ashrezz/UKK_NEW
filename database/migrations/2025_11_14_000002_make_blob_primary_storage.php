<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migrate dari file storage ke BLOB database untuk persistence di Railway
     */
    public function up()
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            // Upgrade BINARY ke LONGBLOB untuk file yang lebih besar (hingga 4GB)
            // BINARY default 255 bytes - terlalu kecil
            // LONGBLOB mendukung hingga 4GB per file
        });

        // Gunakan raw SQL untuk upgrade tipe kolom ke LONGBLOB
        try {
            DB::statement('ALTER TABLE peminjaman MODIFY bukti_pembayaran_blob LONGBLOB');
        } catch (\Throwable $e) {
            // Fallback untuk database yang tidak support LONGBLOB (jarang)
            // Tetap gunakan MEDIUMBLOB (16MB per file)
            try {
                DB::statement('ALTER TABLE peminjaman MODIFY bukti_pembayaran_blob MEDIUMBLOB');
            } catch (\Throwable $e2) {
                \Log::warning('Could not upgrade BLOB column type: ' . $e2->getMessage());
            }
        }
    }

    public function down()
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            // Downgrade kembali ke BINARY
        });

        try {
            DB::statement('ALTER TABLE peminjaman MODIFY bukti_pembayaran_blob BINARY(255)');
        } catch (\Throwable $e) {
            \Log::warning('Could not downgrade BLOB column type: ' . $e->getMessage());
        }
    }
};
