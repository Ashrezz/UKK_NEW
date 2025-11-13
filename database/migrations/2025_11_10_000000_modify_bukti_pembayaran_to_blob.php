<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, create a temporary column as MEDIUMBLOB
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->binary('bukti_pembayaran_blob')->nullable()->after('bukti_pembayaran');
        });

        // Copy existing data to the new column
        DB::statement('UPDATE peminjaman SET bukti_pembayaran_blob = bukti_pembayaran WHERE bukti_pembayaran IS NOT NULL');

        // Drop the old column
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->dropColumn('bukti_pembayaran');
        });

        // Rename the new column to the original name
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->renameColumn('bukti_pembayaran_blob', 'bukti_pembayaran');
        });

        // Set the column type to MEDIUMBLOB using a raw SQL statement
        DB::statement('ALTER TABLE peminjaman MODIFY bukti_pembayaran MEDIUMBLOB');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->string('bukti_pembayaran')->nullable()->change();
        });
    }
};