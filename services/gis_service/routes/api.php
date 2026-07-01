<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicApiController;
use App\Http\Controllers\LahanSawahController;

// RUTE MAP PUBLIK
Route::get('/statistik', [PublicApiController::class, 'getStatistik']);
Route::get('/statistik/kecamatan/{kecamatan}', [PublicApiController::class, 'getDetailStatistikKecamatan']);
Route::get('/statistik/kecamatan/{kecamatan}/excel', [PublicApiController::class, 'downloadStatistikKecamatan']);
Route::get('/batas-wilayah', [PublicApiController::class, 'getBatasWilayah']);
Route::get('/batas-kecamatan', [PublicApiController::class, 'getBatasKecamatan']);
Route::get('/map-lahan', [PublicApiController::class, 'getMapData']);
Route::get('/map-lahan-termonitor', [PublicApiController::class, 'getMapLahanTermonitor']);

// RUTE SPASIAL UNTUK PETUGAS (Dibuat lepas dari Middleware agar tidak 404)
Route::get('/spasial-lahan/referensi', [LahanSawahController::class, 'getReferensiData']);
Route::get('/spasial-lahan', [LahanSawahController::class, 'index']);
Route::post('/spasial-lahan', [LahanSawahController::class, 'store']);
Route::put('/spasial-lahan/{id}', [LahanSawahController::class, 'update']);
Route::delete('/spasial-lahan/{id}', [LahanSawahController::class, 'destroy']);
