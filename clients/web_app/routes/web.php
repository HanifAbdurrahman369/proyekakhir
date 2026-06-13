<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\PetaniController;
use App\Http\Controllers\LahanSawahController;
use App\Http\Controllers\SiklusTanamController;
use App\Http\Controllers\PejabatController;

/*
|--------------------------------------------------------------------------
| Web Routes - Frontend web_app (Port 8080)
|--------------------------------------------------------------------------
| Ini adalah pusat kendali antarmuka (UI) dari sistem SIG-PALA.
| Semua rute di bawah ini bertugas merender Blade HTML dan meneruskan
| operasi logika ke backend via API Gateway (Port 8003).
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});


/*
===================================================================
1. JALUR AUTENTIKASI GLOBAL (AUTH)
===================================================================
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', function () { return view('auth.register'); })->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');

Route::get('/reset-password/{token}', function ($token) {
    return view('auth.reset-password', ['token' => $token]);
})->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Profil Pengguna (Wajib Login JWT)
Route::get('/profile', [AuthController::class, 'profile'])->middleware('jwt');


/*
===================================================================
2. JALUR HALAMAN PUBLIK (STATISTIK & PETA MASYARAKAT)
===================================================================
*/
// Rute Peta Publik
Route::get('/map', function () { return view('fullmap'); })->name('map.full');
Route::get('/map.pejabat', function () { return view('sebaran-lahan'); })->name('map.pejabat');

// SOLUSI ERROR 404: Rute Halaman Statistik memanggil statistik_halaman.blade.php
Route::get('/statistik', function () {
    return view('statistik_halaman'); 
})->name('statistik.publik');


/*
===================================================================
3. PENGALIHAN DASHBOARD MULTI-ROLE (Dipanggil Setelah Login)
===================================================================
*/
Route::get('/dashboard-petani', [PetaniController::class, 'index'])->middleware('role:1');

// Memastikan "case 2: return redirect('/dashboard-petugas');" bekerja sempurna dan memuat data peta
Route::get('/dashboard-petugas', [PetugasController::class, 'index'])->middleware('role:2');

Route::middleware(['role:3'])->group(function () {
    Route::get('/dashboard-pejabat', [PejabatController::class, 'index'])->name('dashboard.pejabat');
    Route::get('/pejabat/cetak-laporan', [PejabatController::class, 'exportDashboardPDF'])->name('pejabat.cetak');
    Route::get('/pejabat/produksi-kecamatan', [PejabatController::class, 'produksiKecamatan'])->name('produksi.kecamatan');
    Route::get('/pejabat/produksi-kecamatan/pdf', [PejabatController::class, 'exportProduksiPDF'])->name('produksi.kecamatan.pdf');
    Route::get('/pejabat/lahan-kecamatan', [PejabatController::class, 'lahanKecamatan'])->name('lahan.kecamatan');
    Route::get('/pejabat/lahan-kecamatan/pdf', [PejabatController::class, 'exportLahanPDF'])->name('lahan.kecamatan.pdf');
});


/*
===================================================================
4. OPERASIONAL PETUGAS (ROLE: 2) - Manajemen Spasial
===================================================================
*/
Route::middleware(['role:2'])->group(function () {
    Route::get('/dashboard-petugas', [PetugasController::class, 'index']);
    Route::get('/manajemen-data-spasial', [PetugasController::class, 'manajemenDataSpasial']);
    Route::get('/input-parameter-lingkungan', [PetugasController::class, 'inputParameterLingkungan']);
    Route::get('/verifikasi-data-petani', [PetugasController::class, 'verifikasiDataPetani']);  
    Route::get('/petugas/pending-counts', [PetugasController::class, 'pendingCounts']);
    Route::post('/petugas/spasial/simpan', [PetugasController::class, 'storeSpasial']);
    Route::put('/petugas/spasial/{id}', [PetugasController::class, 'updateSpasial']);
    Route::delete('/petugas/spasial/{id}', [PetugasController::class, 'destroySpasial']);
    Route::post('/petugas/parameter-lingkungan/simpan', [PetugasController::class, 'storeParameterLingkungan']);
    Route::post('/petugas/verifikasi-lahan/{id}/{aksi}', [PetugasController::class, 'aksiVerifikasiLahan']);
    Route::post('/petugas/verifikasi-panen/{id}/{aksi}', [PetugasController::class, 'aksiVerifikasiPanen']);
    Route::post('/petugas/verifikasi/{id}/{aksi}', [PetugasController::class, 'aksiVerifikasi']);
});

/*
===================================================================
5. OPERASIONAL PETANI (ROLE: 1) - Siklus Tanam
===================================================================
*/
Route::middleware(['role:1'])->group(function () {
    Route::get('/dashboard-petani', [PetaniController::class, 'index']);
    Route::get('/tambah-lahan', [LahanSawahController::class, 'create'])->name('tambah.lahan');
    Route::post('/lahan/store', [LahanSawahController::class, 'storeLahan'])->name('lahan.store');
    Route::get('/lahan/{id}/edit', [LahanSawahController::class, 'edit'])->name('lahan.edit');
    Route::put('/lahan/{id}/resubmit', [LahanSawahController::class, 'resubmitLahan'])->name('lahan.resubmit');
    Route::get('/input-panen', [SiklusTanamController::class, 'create'])->name('input.panen');
    Route::get('/riwayat-panen', [SiklusTanamController::class, 'riwayatPanen'])->name('riwayat.panen');
    Route::post('/input-panen', [SiklusTanamController::class, 'store'])->name('input.panen.store');
});


/*
===================================================================
6. OPERASIONAL ADMIN (ROLE: 4) - Manajemen Akun & Database (DBA)
===================================================================
*/
Route::middleware(['role:4'])->group(function () {
    
    // Halaman Dashboard Admin Terpadu
    Route::get('/dashboard-admin', [AdminUserController::class, 'index']);
    
    // Operasi CRUD Akun User Dinamis
    Route::prefix('admin/users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::post('/', [AdminUserController::class, 'store']);
        Route::put('/{id}', [AdminUserController::class, 'update']);
        Route::delete('/{id}', [AdminUserController::class, 'destroy']);
    });

    // Operasi CRUD Ekosistem Data Master (DBA Service Bypass)
    Route::prefix('admin/master')->group(function () {
        Route::get('/', [MasterDataController::class, 'index']);
        Route::post('/execute-sql', [MasterDataController::class, 'executeSql']);
        Route::get('/export/sql/{tableName?}', [MasterDataController::class, 'exportSql']);
        Route::get('/export/excel/{tableName?}', [MasterDataController::class, 'exportExcel']);
        
        Route::post('/{tableName}', [MasterDataController::class, 'store']);
        Route::put('/{tableName}/{id}', [MasterDataController::class, 'update']);
        Route::delete('/{tableName}/{id}', [MasterDataController::class, 'destroy']);
    });
});
