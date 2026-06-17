<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Exception;

class JwtMiddleware
{
    public function handle($request, Closure $next)
    {
        try {

            $token = $request->bearerToken();

            if (!$token) {

                return response()->json([
                    'message' => 'Token tidak ditemukan'
                ], 401);
            }

            $decoded = JWT::decode(
                $token,
                new Key(env('JWT_SECRET'), 'HS256')
            );

            // SIMPAN KE REQUEST ATTRIBUTE
            $request->attributes->set('auth', $decoded);

        } catch (ExpiredException $e) {

            return response()->json([
                'message' => 'Token sudah kadaluarsa'
            ], 401);

        } catch (SignatureInvalidException $e) {

            return response()->json([
                'message' => 'Signature token tidak valid'
            ], 401);

        } catch (Exception $e) {

            return response()->json([
                'message' => 'Unauthorized',
                'error' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }
}