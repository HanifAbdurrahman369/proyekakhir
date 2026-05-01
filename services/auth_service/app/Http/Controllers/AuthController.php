<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;

class AuthController extends Controller
{
public function login(Request $request)
    {
        // VALIDASI TERMASUK CAPTCHA
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'g-recaptcha-response' => 'required'
        ]);

        /*
        ===================================
        VERIFIKASI CAPTCHA KE GOOGLE
        ===================================
        */

        $captcha = Http::asForm()->post(
            env('CAPTCHA_VERIFY_URL'),
            [
                'secret' => env('CAPTCHA_SECRET_KEY'),
                'response' => $request->input('g-recaptcha-response'),
                'remoteip' => $request->ip()
            ]
        );

        $captchaResult = $captcha->json();

        if (!$captchaResult['success']) {
            return response()->json([
                'message' => 'Captcha tidak valid'
            ], 422);
        }

        /*
        ===================================
        CEK USER KE USER SERVICE
        ===================================
        */

        $response = Http::post(
            'http://127.0.0.1:8002/api/find-user',
            [
                'email' => $validated['email']
            ]
        );

        if ($response->failed()) {
            return response()->json([
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        $user = $response->json();

        /*
        ===================================
        CEK PASSWORD
        ===================================
        */

        if (!Hash::check(
            $validated['password'],
            $user['password']
        )) {
            return response()->json([
                'message' => 'Email atau password salah'
            ], 401);
        }

        /*
        ===================================
        GENERATE JWT
        ===================================
        */

        $payload = [
            'iss' => 'auth-service',
            'sub' => $user['id'],
            'email' => $user['email'],
            'role_id' => $user['role_id'],
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60)
        ];

        $token = JWT::encode(
            $payload,
            env('JWT_SECRET'),
            'HS256'
        );

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'nama_lengkap' => $user['nama_lengkap'],
                'email' => $user['email'],
                'role_id' => $user['role_id']
            ]
        ]);
    }

    public function register(Request $request)
    {
        $response = Http::post('http://localhost:8001/api/register', [
            'nama_lengkap' => $request->nama_lengkap,
            'email' => $request->email,
            'password' => $request->password,
            'password_confirmation' => $request->password_confirmation,
            'no_hp' => $request->nomor_handphone,
            'alamat' => $request->alamat,
        ]);

        if ($response->successful()) {

            return redirect('/login')->with('success', 'Registrasi berhasil, silakan login');

        }

        return back()->withErrors([
            'register' => 'Gagal melakukan registrasi'
        ]);
    }

        public function logout()
    {
        session()->forget([
            'token',
            'user'
        ]);

        return redirect('/login')->with(
            'success',
            'Logout berhasil'
        );
    }
}


