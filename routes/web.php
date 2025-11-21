<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\RuangController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PeminjamanController::class, 'index'])->name('home');

// Login & Register
Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'registerForm']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Password Reset Routes
Route::get('/password/reset', [AuthController::class, 'resetRequestForm'])->name('password.request');
Route::post('/password/reset/send-code', [AuthController::class, 'sendResetCode'])->name('password.send-code');
Route::get('/password/verify', [AuthController::class, 'verifyCodeForm'])->name('password.verify');
Route::post('/password/verify', [AuthController::class, 'verifyCode'])->name('password.verify.post');
Route::get('/password/reset/new', [AuthController::class, 'newPasswordForm'])->name('password.reset.new');
Route::post('/password/reset/update', [AuthController::class, 'updatePassword'])->name('password.update');

// Profile Management (All authenticated users)
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Messages
    Route::get('/messages/create', [MessageController::class, 'create'])->name('messages.create');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::get('/messages/my', [MessageController::class, 'userMessages'])->name('messages.my');
    Route::post('/messages/{id}/confirm', [MessageController::class, 'confirm'])->name('messages.confirm');
});

// Peminjaman
Route::middleware(['auth', 'prevent_direct_access'])->group(function () {
    Route::get('/peminjaman/create', [PeminjamanController::class, 'create'])->name('peminjaman.create');
    Route::post('/peminjaman', [PeminjamanController::class, 'store'])->name('peminjaman.store');
    Route::get('/peminjaman/{id}/edit', [PeminjamanController::class, 'edit'])->name('peminjaman.edit');
    Route::put('/peminjaman/{id}', [PeminjamanController::class, 'update'])->name('peminjaman.update');
    Route::get('/peminjaman/jadwal', [PeminjamanController::class, 'jadwal'])->name('peminjaman.jadwal');

    // Payment routes
    Route::post('/peminjaman/{id}/upload', [PembayaranController::class, 'uploadBukti'])->name('pembayaran.upload');
});

// ✅ PUBLIC: Serve bukti pembayaran dari BLOB database
// PRIMARY: By ID (most reliable) - langsung query BLOB by ID
Route::get('/pembayaran/bukti/blob/{id}', [PembayaranController::class, 'showBuktiBlob'])->name('pembayaran.bukti.blob');

// DEBUG: Check BLOB metadata for a record
Route::get('/pembayaran/debug/blob/{id}', [PembayaranController::class, 'debugBlob'])->name('pembayaran.debug.blob');

// FALLBACK: By filename
Route::get('/pembayaran/bukti/{filename}', [PembayaranController::class, 'showBukti'])->name('pembayaran.bukti');

// Emergency: Populate missing BLOBs (call this if images are missing)
Route::get('/pembayaran/populate-missing-blobs', [PembayaranController::class, 'populateMissingBlobs'])->name('pembayaran.populate-missing-blobs');

// Admin/Petugas
Route::middleware(['auth', 'role:admin,petugas'])->group(function () {
    Route::get('/ruang', [RuangController::class, 'index']);
    // Notifications
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    // Messages for admin/petugas
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages/{id}/read', [MessageController::class, 'markAsRead'])->name('messages.read');
    Route::post('/messages/{id}/reply', [MessageController::class, 'reply'])->name('messages.reply');
    // Kelola peminjaman (akses: admin + petugas)
    Route::get('/peminjaman/manage', [PeminjamanController::class, 'manage'])->name('peminjaman.manage');
    // Laporan peminjaman (admin / petugas)
    Route::get('/peminjaman/laporan', [PeminjamanController::class, 'laporan'])->name('peminjaman.laporan');
    // Manual cleanup trigger (admin or petugas)
    Route::post('/peminjaman/cleanup', [PeminjamanController::class, 'cleanup'])->name('peminjaman.cleanup');
    Route::post('/peminjaman/{id}/restore', [PeminjamanController::class, 'restore'])->name('peminjaman.restore');
    Route::delete('/peminjaman/{id}/force', [PeminjamanController::class, 'forceDelete'])->name('peminjaman.forceDelete');
    Route::post('/peminjaman/{id}/approve', [PeminjamanController::class, 'approve'])->name('peminjaman.approve');
    Route::post('/peminjaman/{id}/reject', [PeminjamanController::class, 'reject'])->name('peminjaman.reject');
    Route::delete('/peminjaman/{id}', [PeminjamanController::class, 'destroy']);
    Route::get('/api/peminjaman/{id}', [PeminjamanController::class, 'detail']);
});

// Admin & Petugas: Room Management
Route::middleware(['auth', 'role:admin,petugas'])->group(function () {
    Route::post('/ruang', [RuangController::class, 'store'])->name('ruang.store');
    Route::put('/ruang/{id}', [RuangController::class, 'update'])->name('ruang.update');
    Route::delete('/ruang/{id}', [RuangController::class, 'destroy'])->name('ruang.destroy');
    // peminjaman manage routes moved to admin+petugas group

    // Payment verification routes
    Route::get('/pembayaran/verifikasi', [PembayaranController::class, 'verifikasiIndex'])->name('pembayaran.verifikasi.index');
    Route::post('/pembayaran/{id}/verifikasi', [PembayaranController::class, 'verifikasi'])->name('pembayaran.verifikasi');
});

// Admin only: User Management (Full CRUD)
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // User Management
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{id}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{id}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    // Legacy route for backward compatibility
    Route::get('/tambah-user', [AdminUserController::class, 'create'])->name('tambah_user.create');
    Route::post('/tambah-user', [AdminUserController::class, 'store'])->name('tambah_user.store');

    // Backup management
    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups/settings', [BackupController::class, 'saveSettings'])->name('backups.settings.save');
    Route::post('/backups/manual', [BackupController::class, 'manual'])->name('backups.manual');
    Route::get('/backups/download/{filename}', [BackupController::class, 'download'])->name('backups.download');
    Route::get('/backups/restore', [BackupController::class, 'restoreForm'])->name('backups.restore.form');
    Route::post('/backups/restore', [BackupController::class, 'restoreUpload'])->name('backups.restore.upload');
});
