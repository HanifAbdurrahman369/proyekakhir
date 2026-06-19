<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;
use App\Models\User;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        /*
        ===================================
        CARI USER LANGSUNG KE DATABASE
        (LEBIH CEPAT DARIPADA HTTP CALL)
        ===================================
        */
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'Email tidak ditemukan di sistem'
            ], 404);
        }

        /*
        ===================================
        CEK PASSWORD
        ===================================
        */
        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Password yang Anda masukkan salah'
            ], 401);
        }

        /*
        ===================================
        GENERATE JWT
        ===================================
        */
        $payload = [
            'iss' => 'auth-service',
            'sub' => $user->id,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'kelompok_id' => $user->kelompok_id ?? null,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60)
        ];

        $token = JWT::encode(
            $payload,
            env('JWT_SECRET', 'secret-key-sementara-untuk-lokal'),
            'HS256'
        );

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'nama_lengkap' => $user->nama_lengkap,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'kelompok_id' => $user->kelompok_id ?? null
            ]
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $response = Http::withoutVerifying()->post(
            'http://127.0.0.1:8002/api/forgot-password',
            ['email' => $request->email]
        );

        return response()->json($response->json(), $response->status());
    }
    
    
    // 3. ENDPOINT BARU: VERIFIKASI TOKEN JWT UNTUK SERVIS LAIN
    // ===================================================================
    // */
    public function verifyToken(Request $request)
    {
        // Mengambil token dari input body atau dari Header Bearer Token
        $token = $request->input('token') ?? $request->bearerToken();

        if (!$token) {
            return response()->json([
                'valid' => false,
                'message' => 'Token tidak disediakan atau kosong'
            ], 400);
        }

        try {
            // Ambil secret key yang sama dengan yang digunakan saat login
            $secret = env('JWT_SECRET', 'secret-key-sementara-untuk-lokal');
            
            // Lakukan decode token JWT
            $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secret, 'HS256'));

            // Jika berhasil decode, kembalikan data payload token (id, email, role_id)
            return response()->json([
                'valid' => true,
                'message' => 'Token sah dan aktif',
                'data' => $decoded
            ], 200);

        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Token telah kedaluwarsa (Expired)'
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Token tidak sah atau telah dimanipulasi',
                'error' => $e->getMessage()
            ], 401);
        }
    }

    public function resetPassword(Request $request)
    {
        $response = Http::withoutVerifying()->post(
            'http://127.0.0.1:8002/api/reset-password',
            $request->all()
        );

        return response()->json($response->json(), $response->status());
    }
}
