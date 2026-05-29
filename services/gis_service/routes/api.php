<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicApiController;
use App\Http\Controllers\LahanSawahController;

// Rute Publik (Akses Terbuka)
Route::get('/statistik', [PublicApiController::class, 'getStatistik']);
Route::get('/batas-wilayah', [PublicApiController::class, 'getBatasWilayah']);
Route::get('/map-lahan', [PublicApiController::class, 'getMapData']); 
Route::get('/spasial-lahan/publik', [LahanSawahController::class, 'index']);  

// Rute Privat Operasional Petugas (Wajib Proteksi Token JWT)
Route::middleware(\App\Http\Middleware\JwtMiddleware::class)->group(function () {
    Route::get('/spasial-lahan/referensi', [LahanSawahController::class, 'getReferensiData']);
    Route::get('/spasial-lahan', [LahanSawahController::class, 'index']);
    Route::post('/spasial-lahan', [LahanSawahController::class, 'store']);
    Route::put('/spasial-lahan/{id}', [LahanSawahController::class, 'update']);
    Route::delete('/spasial-lahan/{id}', [LahanSawahController::class, 'destroy']);
});