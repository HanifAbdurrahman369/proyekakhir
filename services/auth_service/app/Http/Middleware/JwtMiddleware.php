<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'message' => 'Token tidak ditemukan'
            ], 401);
        }

        try {
            $token = str_replace('Bearer ', '', $authHeader);

            $decoded = JWT::decode(
                $token,
                new Key(env('JWT_SECRET'), 'HS256')
            );

            // ambil user dari database berdasarkan sub
            $user = User::find($decoded->sub);

            if (!$user) {
                return response()->json([
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            // simpan user ke request
            $request->attributes->set('user', $user);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token tidak valid',
                'error' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }
}