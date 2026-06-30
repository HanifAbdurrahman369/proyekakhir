<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SiklusTanamController;
use App\Http\Controllers\JenisBibitController;
use App\Http\Controllers\LahanSawahController;
use App\Http\Controllers\StatistikController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\RiwayatPanenController;
use App\Http\Controllers\KecamatanController;
use App\Http\Controllers\KelurahanController;
use App\Http\Controllers\MonitoringKondisiController;
use App\Http\Controllers\TipeLahanController;

Route::middleware(\App\Http\Middleware\JwtMiddleware::class)->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PEJABAT
    |--------------------------------------------------------------------------
    */
    Route::get('/produksi-pejabat', [StatistikController::class, 'produksiPejabat']);
    Route::get('/total-lahan', [StatistikController::class, 'totalLahan']);
    Route::get('/produksi-kecamatan', [StatistikController::class, 'produksiPerKecamatan']);
    Route::get('/lahan-kecamatan', [StatistikController::class, 'lahanPerKecamatan']);
    Route::get('/produksi-bulanan', [StatistikController::class, 'produksiBulanan']);
    Route::get('/top-kecamatan', [StatistikController::class, 'topKecamatan']);

    /*
    |--------------------------------------------------------------------------
    | PETUGAS - NOTIFIKASI
    |--------------------------------------------------------------------------
    */
    Route::get('/notifikasi', [NotifikasiController::class, 'index']);
    Route::get('/notifikasi/petugas', [NotifikasiController::class, 'getNotifikasiPetugas']);
    Route::get('/notifikasi/{id}', [NotifikasiController::class, 'show']);
    Route::put('/notifikasi/{id}/read', [NotifikasiController::class, 'markAsRead']);

    /*
    |--------------------------------------------------------------------------
    | PETUGAS - VERIFIKASI LAHAN PETANI
    |--------------------------------------------------------------------------
    */
    Route::get('/lahan/pending', [LahanSawahController::class, 'pending']);
    Route::get('/lahan/accepted', [LahanSawahController::class, 'accepted']);
    Route::post('/lahan/{id}/approve', [LahanSawahController::class, 'approve']);
    Route::post('/lahan/{id}/reject', [LahanSawahController::class, 'reject']);

    /*
    |--------------------------------------------------------------------------
    | PETUGAS - MONITORING PARAMETER LINGKUNGAN
    |--------------------------------------------------------------------------
    */
    Route::get('/monitoring', [MonitoringKondisiController::class, 'index']);
    Route::post('/monitoring', [MonitoringKondisiController::class, 'store']);
    Route::get('/monitoring/{id}', [MonitoringKondisiController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | PETUGAS - LAHAN TERMONITOR (HUMA INTEGRATION)
    |--------------------------------------------------------------------------
    */
    Route::get('/lahan-termonitor/preview', [\App\Http\Controllers\LahanTermonitorController::class, 'preview']);
    Route::post('/lahan-termonitor/sync', [\App\Http\Controllers\LahanTermonitorController::class, 'sync']);
    Route::get('/lahan-termonitor', [\App\Http\Controllers\LahanTermonitorController::class, 'index']);
    Route::get('/lahan-termonitor/monitoring', [\App\Http\Controllers\LahanTermonitorController::class, 'monitoring']);

    /*
    |--------------------------------------------------------------------------
    | PETANI - LAHAN
    |--------------------------------------------------------------------------
    */
    Route::get('/lahan', [LahanSawahController::class, 'index']);
    Route::get('/lahan/dropdown', [LahanSawahController::class, 'dropdown']);
    Route::post('/lahan', [LahanSawahController::class, 'store']);
    Route::put('/lahan/{id}/resubmit', [LahanSawahController::class, 'resubmit']);
    Route::delete('/lahan/{id}', [LahanSawahController::class, 'destroy']);
    Route::get('/lahan/{id}', [LahanSawahController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | PETUGAS - VERIFIKASI HASIL PANEN PETANI
    |--------------------------------------------------------------------------
    */
    Route::get('/panen/pending', [SiklusTanamController::class, 'getPendingVerifications']);
    Route::post('/panen/{id}/verifikasi', [SiklusTanamController::class, 'verifyHarvest']);
    Route::post('/panen/{id}/approve', [SiklusTanamController::class, 'approve']);
    Route::post('/panen/{id}/reject', [SiklusTanamController::class, 'reject']);

    /*
    |--------------------------------------------------------------------------
    | PETANI - SIKLUS TANAM / HASIL PANEN
    |--------------------------------------------------------------------------
    */
    Route::get('/activities/pending', [SiklusTanamController::class, 'getPendingVerifications']);
    Route::post('/activities/{id}/approve', [SiklusTanamController::class, 'approve']);
    Route::post('/activities/{id}/reject', [SiklusTanamController::class, 'reject']);
    Route::post('/activities/{id}/verifikasi', [SiklusTanamController::class, 'verifyHarvest']);

    // Pemupukan / Siklus Pupuk
    Route::get('/jenis-pupuk', [SiklusTanamController::class, 'getJenisPupuk']);
    Route::get('/my-siklus-tanam', [SiklusTanamController::class, 'getMySiklusTanam']);
    Route::get('/siklus-pupuk', [SiklusTanamController::class, 'getSiklusPupuk']);
    Route::post('/siklus-pupuk', [SiklusTanamController::class, 'storeSiklusPupuk']);

    Route::get('/activities', [SiklusTanamController::class, 'index']);
    Route::post('/activities', [SiklusTanamController::class, 'store']);
    Route::post('/lapor-panen', [SiklusTanamController::class, 'storeLaporPanen']);
    Route::get('/lapor-panen/{id}', [SiklusTanamController::class, 'showLaporPanen']);
    Route::put('/lapor-panen/{id}', [SiklusTanamController::class, 'updateLaporPanen']);
    Route::get('/total-produksi', [SiklusTanamController::class, 'totalProduksi']);
    Route::get('/activities/{id}', [SiklusTanamController::class, 'show']);
    Route::put('/activities/{id}', [SiklusTanamController::class, 'update']);
    Route::delete('/activities/{id}', [SiklusTanamController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | RIWAYAT PANEN
    |--------------------------------------------------------------------------
    */
    Route::get('/riwayat-panen', [RiwayatPanenController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | JENIS BIBIT
    |--------------------------------------------------------------------------
    */
    Route::get('/bibit', [JenisBibitController::class, 'index']);
    Route::get('/bibit/{id}', [JenisBibitController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| DATA WILAYAH PUBLIK
|--------------------------------------------------------------------------
*/
Route::get('/kecamatan', [KecamatanController::class, 'index']);
Route::get('/kecamatan/{id}', [KecamatanController::class, 'show']);

Route::get('/kelurahan', [KelurahanController::class, 'index']);
Route::get('/kelurahan/{id}', [KelurahanController::class, 'show']);

Route::get('/tipe-lahan', [TipeLahanController::class, 'index']);
Route::get('/tipe-lahan/{id}', [TipeLahanController::class, 'show']);
