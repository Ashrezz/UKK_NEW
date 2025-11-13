<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ==================== Auth Routes (Public) ====================
Route::post('/register', [AuthController::class, 'apiRegister']);
Route::post('/login', [AuthController::class, 'apiLogin']);

// ==================== Auth Routes (Protected - Memerlukan Token) ====================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'apiProfile']);
    Route::post('/logout', [AuthController::class, 'apiLogout']);
});

// ==================== Other API Routes ====================
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/peminjaman/jadwal/{date}', [\App\Http\Controllers\PeminjamanController::class, 'getJadwalByDate']);
Route::get('/peminjaman/{id}', [\App\Http\Controllers\PeminjamanController::class, 'detail']);

