<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Ambil token dari header request
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Akses ditolak: Token tidak ditemukan'], 401);
        }

        // 2. Lempar token ke auth_service (Port 8001) untuk divalidasi keasliannya
        $response = Http::withoutVerifying()->post('http://127.0.0.1:8001/api/verify', [
            'token' => $token
        ]);

        $result = $response->json();

        // 3. Jika token palsu/expired
        if ($response->failed() || !isset($result['valid']) || $result['valid'] !== true) {
            return response()->json(['message' => 'Akses ditolak: Token tidak sah atau kedaluwarsa'], 401);
        }

        // Role admin menggunakan ID 4.
        // Sesuaikan angka 1 dengan ID Role Admin di tabel database Anda.
        if (!isset($result['data']['role_id']) || $result['data']['role_id'] != 4) {
            return response()->json(['message' => 'Akses ditolak: Anda tidak memiliki hak akses Admin'], 403);
        }

        // 5. Lolos uji keamanan, izinkan akses CRUD dilanjutkan
        return $next($request);
    }
}
