<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SiklusTanamController;
use App\Http\Controllers\JenisBibitController;
use App\Http\Controllers\LahanSawahController;

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
    Route::get('/notifikasi/petugas', [NotifikasiController::class, 'getNotifikasiPetugas']);
    Route::put('/notifikasi/{id}/read', [NotifikasiController::class, 'markAsRead']);
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

 
    /*
    |--------------------------------------------------------------------------
    | JENIS BIBIT
    |--------------------------------------------------------------------------
    */

 
});
    Route::get('/riwayat-panen',[SiklusTanamController::class, 'riwayatPanen']);

   Route::get('/bibit', [JenisBibitController::class, 'index']);
    Route::get('/bibit/{id}', [JenisBibitController::class, 'show']);

    /*
    |--------------------------------------------------------------------------
    | LAHAN SAWAH
    |--------------------------------------------------------------------------
    */

    Route::get('/lahan', [LahanSawahController::class, 'index']);
    Route::get('/lahan/{id}', [LahanSawahController::class, 'show']);