<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckAdminToken;
/*
=====================================
API USER SERVICE
=====================================
*/

Route::get(
    '/profile',
    [UserController::class, 'profile']
);

Route::post(
    '/find-user',
    [UserController::class, 'findUser']
);

Route::post(
    '/register',
    [UserController::class, 'register']
);

Route::post(
    '/forgot-password',
    [UserController::class, 'forgotPassword']
);

Route::post('/reset-password', [
    UserController::class,
    'resetPassword'
]);
/*
/*
=====================================
API KOMUNITAS / GAPOKTAN
=====================================
*/
use App\Http\Controllers\KomunitasController;

Route::get('/komunitas', [KomunitasController::class, 'index']);
Route::post('/komunitas', [KomunitasController::class, 'store']);
Route::put('/komunitas/{id}', [KomunitasController::class, 'update']);
Route::delete('/komunitas/{id}', [KomunitasController::class, 'destroy']);

/*
=====================================
API CRUD MANAJEMEN USER (UNTUK ADMIN)
=====================================
*/

// Bungkus semua rute CRUD dengan middleware CheckAdminToken
Route::get('/users', [UserController::class, 'index']);
Route::middleware([CheckAdminToken::class])->group(function () {
    Route::post('/users', [UserController::class, 'store']);         
    Route::get('/users/{id}', [UserController::class, 'show']);      
    Route::put('/users/{id}', [UserController::class, 'update']);    
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});