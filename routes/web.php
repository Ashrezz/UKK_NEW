<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\RuangController;
use App\Http\Controllers\AdminUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PeminjamanController::class, 'index'])->name('home');

// Login & Register
Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'registerForm']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Peminjaman
Route::middleware(['auth', 'prevent_direct_access'])->group(function () {
    Route::get('/peminjaman/create', [PeminjamanController::class, 'create'])->name('peminjaman.create');
    Route::post('/peminjaman', [PeminjamanController::class, 'store'])->name('peminjaman.store');
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

// Admin only: Room Management
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post('/ruang', [RuangController::class, 'store']);
    Route::delete('/ruang/{id}', [RuangController::class, 'destroy']);
    // peminjaman manage routes moved to admin+petugas group

    // Payment verification routes
    Route::get('/pembayaran/verifikasi', [PembayaranController::class, 'verifikasiIndex'])->name('pembayaran.verifikasi.index');
    Route::post('/pembayaran/{id}/verifikasi', [PembayaranController::class, 'verifikasi'])->name('pembayaran.verifikasi');
});

// Admin only: Tambah User
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/tambah-user', [AdminUserController::class, 'create'])->name('tambah_user.create');
    Route::post('/tambah-user', [AdminUserController::class, 'store'])->name('tambah_user.store');
});
