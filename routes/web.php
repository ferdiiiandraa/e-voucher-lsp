<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PemakaianController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/', [PageController::class, 'beranda'])->name('beranda');
    Route::get('/kelola', [PageController::class, 'kelola'])->name('kelola');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    // Data (JSON) — dipakai halaman Beranda & Kelola
    Route::prefix('data')->name('data.')->group(function () {
        Route::get('/state', StateController::class)->name('state');
        Route::get('/riwayat', RiwayatController::class)->name('riwayat');
        Route::post('/vouchers', [VoucherController::class, 'store'])->name('vouchers.store');
        Route::put('/vouchers/{voucher}', [VoucherController::class, 'update'])->name('vouchers.update');
        Route::patch('/vouchers/{voucher}/sisa', [VoucherController::class, 'updateSisa'])->name('vouchers.sisa');
        Route::delete('/vouchers/{voucher}', [VoucherController::class, 'destroy'])->name('vouchers.destroy');
        Route::post('/pemakaian', [PemakaianController::class, 'store'])->name('pemakaian.store');
        Route::delete('/pemakaian/{pemakaian}', [PemakaianController::class, 'destroy'])->name('pemakaian.destroy');
        Route::put('/akun/password', [AccountController::class, 'password'])->name('akun.password');
        Route::post('/akun/profil', [AccountController::class, 'profile'])->name('akun.profil');
    });

    // Manajemen pengguna (admin)
    Route::get('/pengguna', [UserController::class, 'index'])->name('pengguna');
    Route::post('/pengguna', [UserController::class, 'store'])->name('pengguna.store');
    Route::put('/pengguna/{user}/password', [UserController::class, 'resetPassword'])->name('pengguna.password');
    Route::patch('/pengguna/{user}/status', [UserController::class, 'toggleActive'])->name('pengguna.status');
    Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])->name('pengguna.destroy');
});
