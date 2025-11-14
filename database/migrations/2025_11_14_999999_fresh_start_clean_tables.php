<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fresh start - drop all tables and recreate cleanly
     */
    public function up(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Drop all existing tables
        Schema::dropIfExists('pembatalans');
        Schema::dropIfExists('peminjaman');
        Schema::dropIfExists('ruang');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create users table
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('no_induk')->nullable()->unique();
            $table->enum('role', ['user', 'admin', 'petugas'])->default('user');
            $table->rememberToken();
            $table->timestamps();
        });

        // Create ruang table
        Schema::create('ruang', function (Blueprint $table) {
            $table->id();
            $table->string('nama_ruang');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        // Create peminjaman table (BLOB-native) using raw SQL for MEDIUMBLOB column
        DB::statement(<<<SQL
            CREATE TABLE peminjaman (
                id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                ruang_id BIGINT UNSIGNED NOT NULL,
                tanggal DATE NOT NULL,
                jam_mulai TIME NOT NULL,
                jam_selesai TIME NOT NULL,
                keperluan LONGTEXT NOT NULL,
                status ENUM('pending', 'approved', 'disetujui', 'rejected', 'ditolak') NOT NULL DEFAULT 'pending',
                status_pembayaran ENUM('belum_bayar', 'menunggu_verifikasi', 'terverifikasi', 'lunas') NOT NULL DEFAULT 'belum_bayar',
                alasan_penolakan LONGTEXT NULL,
                dibatalkan_oleh VARCHAR(255) NULL,
                biaya DECIMAL(10, 2) NOT NULL DEFAULT 0,
                waktu_pembayaran TIMESTAMP NULL,
                bukti_pembayaran_blob MEDIUMBLOB NULL COMMENT 'Binary image data stored as MEDIUMBLOB',
                bukti_pembayaran_mime VARCHAR(255) NULL COMMENT 'MIME type: image/jpeg, image/png, etc',
                bukti_pembayaran_name VARCHAR(255) NULL COMMENT 'Original filename',
                bukti_pembayaran_size MEDIUMINT UNSIGNED NULL COMMENT 'Size in bytes',
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL,
                deleted_at TIMESTAMP NULL,
                
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (ruang_id) REFERENCES ruang(id) ON DELETE CASCADE,
                
                INDEX idx_user_id (user_id),
                INDEX idx_ruang_id (ruang_id),
                INDEX idx_tanggal (tanggal),
                INDEX idx_status (status),
                INDEX idx_status_pembayaran (status_pembayaran),
                INDEX idx_tanggal_jam (tanggal, jam_mulai, jam_selesai)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL);

        // Create pembatalans table
        Schema::create('pembatalans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peminjaman_id')->constrained('peminjaman')->onDelete('cascade');
            $table->text('alasan');
            $table->timestamps();
        });

        // Create sessions table
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Create password reset tokens table
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        Schema::dropIfExists('pembatalans');
        Schema::dropIfExists('peminjaman');
        Schema::dropIfExists('ruang');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
