<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\DB;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token tidak ditemukan'], 401);
        }

        $jwtSecret = env('JWT_SECRET', 'your-secret-key-here');

        try {
            $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));

            // Cek apakah token expired
            if (isset($decoded->exp) && $decoded->exp < time()) {
                return response()->json(['message' => 'Token expired'], 401);
            }

            // Cek apakah user masih ada di database
            if (!isset($decoded->sub)) {
                return response()->json(['message' => 'Token tidak valid - sub missing'], 401);
            }

            $user = DB::table('users')->where('id', $decoded->sub)->first();
            if (!$user) {
                return response()->json(['message' => 'User tidak ditemukan'], 401);
            }

            // Attach user data ke request untuk digunakan di controller
            $request->merge(['auth_user' => $user]);

            return $next($request);

        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json(['message' => 'Token expired'], 401);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            return response()->json(['message' => 'Token signature invalid'], 401);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token tidak valid',
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 401);
        }
    }
}
