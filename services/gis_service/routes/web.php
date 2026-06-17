<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicApiController;

Route::get('/statistik', [PublicApiController::class, 'getStatistik']);
Route::get('/map-lahan', [PublicApiController::class, 'getMapData']);
Route::get('/batas-wilayah', [PublicApiController::class, 'getBatasWilayah']); // RUTE BARU
Route::get('/batas-kecamatan', [PublicApiController::class, 'getBatasKecamatan']);
