<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SiklusTanamController;
use App\Http\Controllers\JenisBibitController;
use App\Http\Controllers\LahanSawahController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\RiwayatPanenController;
use App\Http\Controllers\KecamatanController;
use App\Http\Controllers\KelurahanController;
use App\Http\Controllers\TipeLahanController;
use App\Http\Controllers\StatistikController;



/*
|--------------------------------------------------------------------------
| API ROUTES
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| SIKLUS TANAM
|--------------------------------------------------------------------------
*/
Route::middleware(\App\Http\Middleware\JwtMiddleware::class)->group(function () {

    //pejabat
    Route::get('/produksi-pejabat',[StatistikController::class, 'produksiPejabat']);
    Route::get('/total-lahan', [StatistikController::class, 'totalLahan']);
    Route::get('/produksi-kecamatan', [StatistikController::class, 'produksiPerKecamatan']);
    Route::get('/lahan-kecamatan', [StatistikController::class, 'lahanPerKecamatan']);
    Route::get('/produksi-bulanan', [StatistikController::class, 'produksiBulanan']);
    Route::get('/top-kecamatan', [StatistikController::class, 'topKecamatan']);

    //petugas
    Route::get('/notifikasi/petugas', [NotifikasiController::class, 'getNotifikasiPetugas']);
    Route::put('/notifikasi/{id}/read', [NotifikasiController::class, 'markAsRead']);

    //petani
    Route::get('/activities', [SiklusTanamController::class, 'index']);
    Route::post('/activities', [SiklusTanamController::class, 'store']);
    Route::get('/total-produksi', [SiklusTanamController::class, 'totalProduksi']);
    Route::get('/activities/{id}', [SiklusTanamController::class, 'show']);
    Route::put('/activities/{id}', [SiklusTanamController::class, 'update']);
    Route::delete('/activities/{id}', [SiklusTanamController::class, 'destroy']);
    Route::get('/activities/pending', [SiklusTanamController::class, 'getPendingVerifications']);
    
    /*
    |--------------------------------------------------------------------------
    | APPROVAL PETUGAS
    |--------------------------------------------------------------------------
    */

    Route::post('/activities/{id}/approve', [SiklusTanamController::class, 'approve']);
    Route::post('/activities/{id}/reject', [SiklusTanamController::class, 'reject']);

    Route::get('/riwayat-panen', [RiwayatPanenController::class, 'index']);
    
    /*
    |--------------------------------------------------------------------------
    | JENIS BIBIT
    |--------------------------------------------------------------------------
    */
    Route::get('/bibit', [JenisBibitController::class, 'index']);
    Route::get('/bibit/{id}', [JenisBibitController::class, 'show']);

    /*
    |--------------------------------------------------
    | LAHAN SAWAH
    |--------------------------------------------------
    */

    Route::get('/lahan', [LahanSawahController::class, 'index']);
    Route::get('/lahan/dropdown', [LahanSawahController::class, 'dropdown']);
    Route::post('/lahan', [LahanSawahController::class, 'store']);
    Route::get('/lahan/{id}', [LahanSawahController::class, 'show']); 



});


        /*
    |--------------------------------------------------------------------------
    | KECAMATAN
    |--------------------------------------------------------------------------
    */
    Route::get('/kecamatan', [KecamatanController::class, 'index']);
    Route::get('/kecamatan/{id}', [KecamatanController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | KELURAHAN
    |--------------------------------------------------------------------------
    */
    Route::get('/kelurahan', [KelurahanController::class, 'index']);
    Route::get('/kelurahan/{id}', [KelurahanController::class, 'show']);

            /*
    |--------------------------------------------------------------------------
    | TIPE LAHAN
    |--------------------------------------------------------------------------
    */
    Route::get('/tipe-lahan', [TipeLahanController::class, 'index']);
    Route::get('/tipe-lahan/{id}', [TipeLahanController::class, 'show']);


 
