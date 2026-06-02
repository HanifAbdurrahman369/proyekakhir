<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes - Auth Service Port 8001
|--------------------------------------------------------------------------
| Service ini hanya menangani autentikasi dan penerbitan token JWT.
| Jangan proxy balik ke API Gateway dari service ini.
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'service' => 'auth_service',
        'port' => 8001,
        'message' => 'Auth Service berjalan normal'
    ]);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/verify', [AuthController::class, 'verifyToken']);

Route::middleware('jwt')->get('/profile', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'Profile berhasil diakses',
        'user' => $request->attributes->get('user')
    ]);
});