<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SiklusTanamController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', [AuthController::class, 'register']);

Route::get('/logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->name('password.request');

Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])
    ->name('password.email');

Route::get('/reset-password/{token}', function ($token) {
    return view('auth.reset-password', [
        'token' => $token
    ]);
})->name('password.reset');

Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->name('password.update');


Route::get('/dashboard-petani', function () {
    return view('dashboard.petani');
})->middleware('role:1');
    
Route::get('/dashboard-petugas', function () {
    return view('dashboard.petugas');
})->middleware('role:2');

Route::get('/dashboard-pejabat', function () {
    return view('dashboard.pejabat');
})->middleware('role:3');

Route::get('/dashboard-admin', function () {
    return view('dashboard.admin');
})->middleware('role:4');

Route::get('/profile', [AuthController::class, 'profile'])
    ->middleware('jwt');

Route::get('/map', function () {
    return view('fullmap');
})->name('map.full');
Route::get('/data-statistik', function () {
    return view('statistik_halaman');
});

Route::get('/input-panen', [SiklusTanamController::class, 'create'])
        ->name('input.panen');

Route::post('/input-panen', [SiklusTanamController::class, 'store'])
        ->name('input.panen.store');
