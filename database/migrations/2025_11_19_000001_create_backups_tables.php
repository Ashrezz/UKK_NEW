<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('backup_settings', function (Blueprint $table) {
            $table->id();
            // Frequency unit: day, week, month, year
            $table->string('frequency_unit')->default('week');
            // Interval: e.g. every 1 week, 2 weeks, etc.
            $table->unsignedInteger('frequency_interval')->default(1);
            // Time of day (HH:MM) server timezone for running backup
            $table->string('run_time')->default('02:00');
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();
        });

        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('driver')->default(config('database.default'));
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
        Schema::dropIfExists('backup_settings');
    }
};
