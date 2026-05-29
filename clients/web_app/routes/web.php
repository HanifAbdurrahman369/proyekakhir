<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\SiklusTanamController;

/*
|--------------------------------------------------------------------------
| Web Routes - Frontend web_app (Port 8080)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

/*
===================================================================
1. JALUR AUTENTIKASI GLOBAL (AUTH_SERVICE KONEKSI)
===================================================================
*/
Route::get('/login', function () {
    return view('auth.login');
})->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', function () {
    return view('auth.register');
})->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');

Route::get('/reset-password/{token}', function ($token) {
    return view('auth.reset-password', ['token' => $token]);
})->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');


/*
===================================================================
2. JALUR DASHBOARD NON-ADMIN (DENGAN MIDDLEWARE ROLE)
===================================================================
*/
Route::get('/dashboard-petani', function () {
    return view('dashboard.petani');
})->middleware('role:1');

Route::get('/dashboard-petugas', [PetugasController::class, 'index'])->middleware('role:2');

Route::get('/dashboard-pejabat', function () {
    return view('dashboard.pejabat');
})->middleware('role:3');


/*
===================================================================
2.1 JALUR INPUT DATA PETANI (ROLE: 1 - PETANI)
===================================================================
*/
Route::middleware(['role:1'])->group(function () {
    Route::get('/input-panen', [SiklusTanamController::class, 'create'])->name('input.panen');
    Route::get('/riwayat-panen', [SiklusTanamController::class, 'riwayatPanen'])->name('riwayat.panen');
    Route::post('/input-panen', [SiklusTanamController::class, 'store'])->name('input.panen.store');
});


/*
===================================================================
3. REVISI KHUSUS: JALUR MANAJEMEN ADMIN (ROLE: 4)
===================================================================
*/
Route::middleware(['role:4'])->group(function () {
    
    // Halaman utama Dashboard Admin kini memanggil data user secara dinamis dari user_service
    Route::get('/dashboard-admin', [AdminUserController::class, 'index']);
    
    // API Route internal Frontend untuk interaksi Form CRUD di admin.blade.php
    Route::prefix('admin/users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index']); // Alias / sinkronisasi menu sidebar
        Route::post('/', [AdminUserController::class, 'store']); // Aksi Tambah User
        Route::put('/{id}', [AdminUserController::class, 'update']); // Aksi Edit User
        Route::delete('/{id}', [AdminUserController::class, 'destroy']); // Aksi Hapus User
    });

    // JALUR DATA MASTER DINAMIS
    Route::prefix('admin/master')->group(function () {
        Route::get('/', [MasterDataController::class, 'index']);
        Route::post('/execute-sql', [MasterDataController::class, 'executeSql']);
        Route::get('/export/sql/{tableName?}', [MasterDataController::class, 'exportSql']);
        Route::get('/export/excel/{tableName?}', [MasterDataController::class, 'exportExcel']);
        
        // CRUD Dinamis (Berdasarkan Nama Tabel)
        Route::post('/{tableName}', [MasterDataController::class, 'store']);
        Route::put('/{tableName}/{id}', [MasterDataController::class, 'update']);
        Route::delete('/{tableName}/{id}', [MasterDataController::class, 'destroy']);
    });
});


/*
===================================================================
4. JALUR LAINNYA (PROFILE, MAP, & PETUGAS)
===================================================================
*/
Route::get('/profile', [AuthController::class, 'profile'])->middleware('jwt');

Route::get('/map', function () {
    return view('fullmap'); 
})->name('map.full');

Route::get('/map.pejabat', function () {
    return view('sebaran-lahan');
})->name('map.pejabat');

Route::prefix('petugas')->group(function () {
    Route::get('/dashboard', [PetugasController::class, 'index']);
    Route::post('/spasial/simpan', [PetugasController::class, 'storeSpasial']);
    Route::put('/spasial/ubah/{id}', [PetugasController::class, 'updateSpasial']);
    Route::delete('/spasial/hapus/{id}', [PetugasController::class, 'destroySpasial']);
});