<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicApiController;
use App\Http\Controllers\LahanSawahController;

// RUTE MAP PUBLIK
Route::get('/statistik', [PublicApiController::class, 'getStatistik']);
Route::get('/batas-wilayah', [PublicApiController::class, 'getBatasWilayah']);
Route::get('/map-lahan', [PublicApiController::class, 'getMapData']); 

// RUTE SPASIAL UNTUK PETUGAS (Dibuat lepas dari Middleware agar tidak 404)
Route::get('/spasial-lahan/referensi', [LahanSawahController::class, 'getReferensiData']);
Route::get('/spasial-lahan', [LahanSawahController::class, 'index']);
Route::post('/spasial-lahan', [LahanSawahController::class, 'store']);
Route::put('/spasial-lahan/{id}', [LahanSawahController::class, 'update']);
Route::delete('/spasial-lahan/{id}', [LahanSawahController::class, 'destroy']);