<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SiklusTanamController;
use App\Http\Controllers\JenisBibitController;
use App\Http\Controllers\LahanSawahController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\RiwayatPanenController;
use App\Http\Controllers\KecamatanController;
use App\Http\Controllers\KelurahanController;


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
    //petugas
    Route::get('/notifikasi/petugas', [NotifikasiController::class, 'getNotifikasiPetugas']);
    Route::put('/notifikasi/{id}/read', [NotifikasiController::class, 'markAsRead']);

    //petani
    Route::get('/activities', [SiklusTanamController::class, 'index']);
    Route::post('/activities', [SiklusTanamController::class, 'store']);
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


 
