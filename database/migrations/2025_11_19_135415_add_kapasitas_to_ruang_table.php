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
        Schema::table('ruang', function (Blueprint $table) {
            // Check if column doesn't exist before adding
            if (!Schema::hasColumn('ruang', 'kapasitas')) {
                $table->integer('kapasitas')->default(0)->after('nama_ruang');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ruang', function (Blueprint $table) {
            if (Schema::hasColumn('ruang', 'kapasitas')) {
                $table->dropColumn('kapasitas');
            }
        });
    }
};
