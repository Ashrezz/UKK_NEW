<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pembatalans', function (Blueprint $table) {
            // Drop the existing foreign key if it exists
            $table->dropForeign(['peminjaman_id']);
            
            // Add the correct foreign key
            $table->foreign('peminjaman_id')
                  ->references('id')
                  ->on('peminjaman')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembatalans', function (Blueprint $table) {
            $table->dropForeign(['peminjaman_id']);
            
            // Re-add the original foreign key
            $table->foreign('peminjaman_id')
                  ->references('id')
                  ->on('peminjaman')
                  ->onDelete('cascade');
        });
    }
};