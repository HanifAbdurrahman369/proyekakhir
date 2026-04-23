<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/profile', function (Request $request) {

    if ($request->header('Authorization') != 'Bearer dummy-token-123') {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    return response()->json([
        'name' => 'Hanif',
        'role' => 'Admin'
    ]);
});
