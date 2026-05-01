<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

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