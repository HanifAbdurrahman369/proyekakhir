<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Http;


Route::post('/login', function (Request $request) {

    $response = Http::post('http://127.0.0.1:8001/api/login', [
        'email' => $request->email,
        'password' => $request->password
    ]);

    return $response->json();
});
