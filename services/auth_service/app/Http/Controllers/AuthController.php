<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Firebase\JWT\JWT;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'login_id' => 'required|string',
            'password' => 'required|string',
        ]);

        /*
        ===================================
        CARI USER LANGSUNG KE DATABASE
        (LEBIH CEPAT DARIPADA HTTP CALL)
        ===================================
        */
        $user = User::where('nik', $validated['login_id'])
            ->orWhere('nip', $validated['login_id'])
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'NIK atau NIP tidak ditemukan di sistem'
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
            'nik' => $user->nik,
            'nip' => $user->nip,
            'role_id' => $user->role_id,
            'komunitas_id' => $user->komunitas_id ?? null,
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
            'user' => $this->formatUser($user)
        ]);
    }

    public function forgotPassword(Request $request)
    {
        try {
            $response = Http::withoutVerifying()
                ->acceptJson()
                ->timeout(15)
                ->post(
                    'http://127.0.0.1:8002/api/forgot-password',
                    ['email' => $request->email]
                );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Layanan user_service belum dapat dihubungi untuk reset password.',
            ], 502);
        }

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
        try {
            $response = Http::withoutVerifying()
                ->acceptJson()
                ->timeout(15)
                ->post(
                    'http://127.0.0.1:8002/api/reset-password',
                    $request->all()
                );
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Layanan user_service belum dapat dihubungi untuk reset password.',
            ], 502);
        }

        return response()->json($response->json(), $response->status());
    }

    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Profile berhasil diakses',
            'user' => $this->formatUser($request->attributes->get('user')),
        ]);
    }

    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->attributes->get('user');

        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
            'wilayah_kecamatan_id' => ['nullable', 'integer'],
            'wilayah_kelurahan_id' => ['nullable', 'integer'],
        ]);

        $user->fill([
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'],
            'no_hp' => $validated['no_hp'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
        ]);
        $user->save();

        if ($user->role_id == 2 && !empty($user->komunitas_id)) {
            $kelurahan_ids = null;
            if (!empty($validated['wilayah_kelurahan_id'])) {
                $kelurahan_ids = json_encode([(int) $validated['wilayah_kelurahan_id']]);
            }

            DB::table('komunitas')->where('id', $user->komunitas_id)->update([
                'wilayah_kecamatan_id' => $validated['wilayah_kecamatan_id'] ?? null,
                'wilayah_kelurahan_ids' => $kelurahan_ids,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    private function formatUser(?User $user): ?array
    {
        if (!$user) {
            return null;
        }

        $komunitas = null;
        $kelurahanIds = [];
        $kelurahanNames = [];

        if (!empty($user->komunitas_id) && Schema::hasTable('komunitas')) {
            $komunitas = DB::table('komunitas')
                ->where('id', $user->komunitas_id)
                ->first();
                
            if ($komunitas) {
                $kelurahanIds = $this->kelurahanIds($komunitas->wilayah_kelurahan_ids ?? null);
                $kelurahanNames = empty($kelurahanIds)
                    ? []
                    : DB::table('kelurahan')
                        ->whereIn('id', $kelurahanIds)
                        ->orderBy('nama_kelurahan')
                        ->pluck('nama_kelurahan')
                        ->values()
                        ->all();
            }
        }

        return [
            'id' => (int) $user->id,
            'nama_lengkap' => $user->nama_lengkap,
            'email' => $user->email,
            'nik' => $user->nik,
            'nip' => $user->nip,
            'role_id' => $user->role_id !== null ? (int) $user->role_id : null,
            'komunitas_id' => $user->komunitas_id !== null ? (int) $user->komunitas_id : null,
            'no_hp' => $user->no_hp,
            'alamat' => $user->alamat,
            'wilayah_kecamatan_id' => $komunitas->wilayah_kecamatan_id ?? null,
            'wilayah_kecamatan_nama' => ($komunitas && $komunitas->wilayah_kecamatan_id)
                ? DB::table('kecamatan')->where('id', $komunitas->wilayah_kecamatan_id)->value('nama_kecamatan')
                : null,
            'wilayah_kelurahan_ids' => $kelurahanIds,
            'wilayah_kelurahan_nama' => $kelurahanNames,
            'instansi_asal' => $komunitas->instansi_asal ?? null,
            'nama_bpp' => $komunitas->nama_bpp ?? null,
        ];
    }

    private function kelurahanIds($value): array
    {
        if (is_array($value)) {
            return array_values(array_unique(array_map('intval', $value)));
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values(array_unique(array_map('intval', $decoded)));
            }
        }

        return [];
    }
}
