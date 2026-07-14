<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\KomunitasAdminController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PetugasController;
use App\Http\Controllers\PetaniController;
use App\Http\Controllers\LahanSawahController;
use App\Http\Controllers\SiklusTanamController;
use App\Http\Controllers\PejabatController;
use App\Http\Controllers\ProduksiDaerahController;
use App\Http\Controllers\MobileAppDownloadController;

/*
|--------------------------------------------------------------------------
| Web Routes - Frontend web_app (Port 8080)
|--------------------------------------------------------------------------
| Ini adalah pusat kendali antarmuka (UI) dari sistem SiPetani.
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
Route::get('/download-mobile-app', [MobileAppDownloadController::class, 'download'])->name('mobile-app.download');
Route::get('/download-mobile-app/file', [MobileAppDownloadController::class, 'downloadFile'])->name('mobile-app.file');

Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');

Route::get('/reset-password/{token}', function ($token) {
    return view('auth.reset-password', ['token' => $token]);
})->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Profil Pengguna
Route::middleware(['role:1,2,3,4,5'])->group(function () {
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
});


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
Route::get('/statistik/kecamatan/{kecamatan}', function ($kecamatan) {
    return view('statistik_kecamatan_detail', ['kecamatanIdentifier' => $kecamatan]);
})->name('statistik.kecamatan.detail');


/*
===================================================================
3. PENGALIHAN DASHBOARD MULTI-ROLE (Dipanggil Setelah Login)
===================================================================
*/
Route::get('/dashboard-petani', [PetaniController::class, 'index'])->middleware('role:1,5');

// Memastikan "case 2: return redirect('/dashboard-petugas');" bekerja sempurna dan memuat data peta
Route::get('/dashboard-petugas', [PetugasController::class, 'index'])->middleware('role:2');

Route::get('/pejabat/cetak-laporan', [PejabatController::class, 'exportDashboardPDF'])->name('pejabat.cetak');
Route::get('/pejabat/lahan-sawah/pdf', [PejabatController::class, 'exportLahanSawahPDF'])->name('pejabat.lahan_sawah.pdf');
Route::get('/pejabat/lahan-sawah/excel', [PejabatController::class, 'exportLahanSawahExcel'])->name('pejabat.lahan_sawah.excel');
Route::get('/pejabat/produksi-kecamatan/excel', [PejabatController::class, 'exportProduksiExcel'])->name('pejabat.produksi_kecamatan.excel');
Route::get('/pejabat/produksi-kecamatan/pdf', [PejabatController::class, 'exportProduksiPDF'])->name('produksi.kecamatan.pdf');
Route::get('/pejabat/lahan-kecamatan/pdf', [PejabatController::class, 'exportLahanPDF'])->name('lahan.kecamatan.pdf');
Route::get('/pejabat/produksi-kelurahan/pdf', [PejabatController::class, 'exportProduksiKelurahanPDF'])->name('pejabat.produksi_kelurahan.pdf');
Route::get('/pejabat/produksi-kelurahan/excel', [PejabatController::class, 'exportProduksiKelurahanExcel'])->name('pejabat.produksi_kelurahan.excel');

Route::middleware(['role:3'])->group(function () {
    Route::get('/dashboard-pejabat', [PejabatController::class, 'index'])->name('dashboard.pejabat');
    Route::get('/pejabat/produksi-kecamatan', [PejabatController::class, 'produksiKecamatan'])->name('produksi.kecamatan');
    Route::get('/pejabat/lahan-kecamatan', [PejabatController::class, 'lahanKecamatan'])->name('lahan.kecamatan');
    Route::get('/laporan-produksi', [ProduksiDaerahController::class, 'index'])->name('laporan.produksi');
    Route::get('/api/produksi-daerah', [ProduksiDaerahController::class, 'data'])->name('laporan.produksi.data');
});


/*
===================================================================
4. OPERASIONAL PETUGAS (ROLE: 2) - Manajemen Spasial
===================================================================
*/
Route::middleware(['role:2'])->group(function () {
    Route::get('/dashboard-petugas', [PetugasController::class, 'index']);
    Route::get('/manajemen-data-spasial', [PetugasController::class, 'manajemenDataSpasial']);
    Route::get('/lahan-termonitor', [PetugasController::class, 'lahanTermonitor']);
    Route::get('/verifikasi-data-petani', [PetugasController::class, 'verifikasiDataPetani']);  
    Route::get('/manajemen-komunitas', [PetugasController::class, 'manajemenKomunitas']);
    Route::post('/petugas/komunitas', [PetugasController::class, 'storeKomunitas']);
    Route::put('/petugas/komunitas/{id}', [PetugasController::class, 'updateKomunitas']);
    Route::delete('/petugas/komunitas/{id}', [PetugasController::class, 'destroyKomunitas']);
    Route::get('/petugas/pending-counts', [PetugasController::class, 'pendingCounts']);
    Route::post('/petugas/lahan-termonitor/sync', [PetugasController::class, 'syncLahanTermonitor']);
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
5. OPERASIONAL PETANI - Kelompok Tani dan Brigade Pangan
===================================================================
*/
Route::middleware(['role:1,5'])->group(function () {
    Route::get('/dashboard-petani', [PetaniController::class, 'index'])->name('petani.dashboard');
    Route::get('/lapor-tanam', [SiklusTanamController::class, 'create'])->name('lapor.tanam');
    Route::post('/lapor-tanam', [SiklusTanamController::class, 'store'])->name('lapor.tanam.store');
    Route::get('/lapor-tanam/{id}/edit', [SiklusTanamController::class, 'editTanam'])->name('lapor.tanam.edit');
    Route::put('/lapor-tanam/{id}', [SiklusTanamController::class, 'updateTanam'])->name('lapor.tanam.update');
    Route::delete('/lapor-tanam/{id}', [SiklusTanamController::class, 'destroyTanam'])->name('lapor.tanam.destroy');
    Route::get('/riwayat-panen', [SiklusTanamController::class, 'riwayatPanen'])->name('riwayat.panen');
    Route::post('/input-pemupukan', [SiklusTanamController::class, 'storePemupukan'])->name('input.pemupukan.store');
});

Route::middleware(['role:1,5'])->group(function () {
    Route::get('/tambah-lahan', [LahanSawahController::class, 'create'])->name('tambah.lahan');
    Route::post('/lahan/store', [LahanSawahController::class, 'storeLahan'])->name('lahan.store');
    Route::get('/lahan/{id}/edit', [LahanSawahController::class, 'edit'])->name('lahan.edit');
    Route::put('/lahan/{id}/resubmit', [LahanSawahController::class, 'resubmitLahan'])->name('lahan.resubmit');
    Route::delete('/lahan/{id}', [LahanSawahController::class, 'destroyLahan'])->name('lahan.destroy');
    Route::get('/lapor-panen', [SiklusTanamController::class, 'createLaporPanen'])->name('lapor.panen');
    Route::post('/lapor-panen', [SiklusTanamController::class, 'storeLaporPanen'])->name('lapor.panen.store');
    Route::get('/panen/{id}/edit', [SiklusTanamController::class, 'edit'])->name('panen.edit');
    Route::put('/panen/{id}/update', [SiklusTanamController::class, 'update'])->name('panen.update');
});


/*
===================================================================
6. OPERASIONAL ADMIN (ROLE: 4) - Manajemen Akun & Database (DBA)
===================================================================
*/
Route::middleware(['role:4'])->group(function () {
    
    // Halaman Dashboard Admin Terpadu
    Route::get('/dashboard-admin', [AdminUserController::class, 'dashboard']);
    
    // Operasi CRUD Akun User Dinamis
    Route::prefix('admin/users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::post('/', [AdminUserController::class, 'store']);
        Route::put('/{id}', [AdminUserController::class, 'update']);
        Route::delete('/{id}', [AdminUserController::class, 'destroy']);
    });

    // Operasi CRUD Komunitas
    Route::prefix('admin/komunitas')->group(function () {
        Route::post('/', [KomunitasAdminController::class, 'store']);
        Route::put('/{id}', [KomunitasAdminController::class, 'update']);
        Route::delete('/{id}', [KomunitasAdminController::class, 'destroy']);
        Route::post('/import', [KomunitasAdminController::class, 'import']);
        Route::get('/export', [KomunitasAdminController::class, 'export']);
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
