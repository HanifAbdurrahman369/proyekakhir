<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('auth.login');
});

Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', function () {
    return view('auth.register');
});

Route::post('/register', [AuthController::class, 'register']);

Route::get('/logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
});

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