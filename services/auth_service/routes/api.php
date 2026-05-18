<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Http\Controllers\AuthController;

$jwtSecret = env('JWT_SECRET', 'your-secret-key-here');

Route::post('/login', [AuthController::class, 'login']);

/*Route::post('/login', function (Request $request) {
    try {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        // Request ke user_service
        $response = Http::post('http://127.0.0.1:8002/api/find-user', [
            'email' => $validated['email']
        ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        $user = $response->json();

        // Cek password
        if (!Hash::check($validated['password'], $user['password'])) {
            return response()->json([
                'message' => 'Email atau password salah'
            ], 401);
        }

        // Generate JWT
        $payload = [
            'iss' => 'auth-service',
            'sub' => $user['id'],
            'email' => $user['email'],
            'role_id' => $user['role_id'],
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60)
        ];

        // Gunakan env('JWT_SECRET')
        $token = JWT::encode(
            $payload,
            env('JWT_SECRET'),
            'HS256'
        );

        return response()->json([
            'message' => 'Login berhasil',
            'user' => [
                'id' => $user['id'],
                'nama_lengkap' => $user['nama_lengkap'],
                'email' => $user['email']
            ],
            'token' => $token
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan',
            'error' => $e->getMessage()
        ], 500);
    }
});*/


Route::middleware('jwt')->get('/profile', function (Request $request) {
    return response()->json([
        'message' => 'Profile berhasil diakses',
        'user' => $request->attributes->get('user')
    ]);

    /*
=====================================
API AUTH SERVICE
=====================================
*/

Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// ROUTE BARU: Digunakan oleh API Gateway, User Service, atau Master Service untuk validasi token
Route::post('/verify', [AuthController::class, 'verifyToken']);
});
Route::post('/verify', [AuthController::class, 'verifyToken']);

