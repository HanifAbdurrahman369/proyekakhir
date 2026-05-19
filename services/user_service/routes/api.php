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
API CRUD MANAJEMEN USER (UNTUK ADMIN)
=====================================
*/

// Bungkus semua rute CRUD dengan middleware CheckAdminToken
Route::middleware([CheckAdminToken::class])->group(function () {
    Route::get('/users', [UserController::class, 'index']);          
    Route::post('/users', [UserController::class, 'store']);         
    Route::get('/users/{id}', [UserController::class, 'show']);      
    Route::put('/users/{id}', [UserController::class, 'update']);    
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});