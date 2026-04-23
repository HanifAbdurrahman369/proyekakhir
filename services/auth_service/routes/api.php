<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$jwtSecret = env('JWT_SECRET', 'your-secret-key-here');

Route::post('/login', function (Request $request) use ($jwtSecret) {
    try {
        // Validasi request
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Generate JWT Token
        $payload = [
            'iss' => 'auth-service',
            'sub' => $user->id,
            'email' => $user->email,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 jam
        ];

        $token = JWT::encode($payload, $jwtSecret, 'HS256');

        return response()->json([
            'message' => 'Login berhasil',
            'user' => $user,
            'token' => $token
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});

Route::post('/register', function (Request $request) use ($jwtSecret) {
    try {
        // Validasi request
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'no_hp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string|max:500'
        ]);

        // Cek apakah email sudah terdaftar
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser) {
            return response()->json(['message' => 'Email sudah terdaftar'], 409);
        }

        // Get role_id untuk petani (otomatis untuk guest registration)
        $petaniRole = DB::table('roles')->where('nama_role', 'petani')->first();
        if (!$petaniRole) {
            return response()->json(['message' => 'Role petani tidak ditemukan'], 500);
        }

        // Create user baru dengan role petani otomatis
        $user = User::create([
            'role_id' => $petaniRole->id, // otomatis petani
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'no_hp' => $validated['no_hp'] ?? null,
            'alamat' => $validated['alamat'] ?? null
        ]);

        // Generate JWT Token
        $payload = [
            'iss' => 'auth-service',
            'sub' => $user->id,
            'email' => $user->email,
            'role' => 'petani',
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 jam
        ];

        $token = JWT::encode($payload, $jwtSecret, 'HS256');

        return response()->json([
            'message' => 'Registrasi berhasil sebagai petani',
            'user' => $user,
            'token' => $token
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});

// Endpoint untuk admin menambahkan user (petugas/penjabat)
Route::middleware('jwt')->post('/admin/users', function (Request $request) use ($jwtSecret) {
    try {
        // TODO: Tambahkan middleware JWT untuk verifikasi admin
        // Untuk sementara, langsung process (dalam production harus ada auth)

        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:petugas,penjabat', // hanya bisa tambah petugas atau penjabat
            'no_hp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string|max:500'
        ]);

        // Cek apakah email sudah terdaftar
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser) {
            return response()->json(['message' => 'Email sudah terdaftar'], 409);
        }

        // Get role_id berdasarkan role yang diminta
        $role = DB::table('roles')->where('nama_role', $validated['role'])->first();
        if (!$role) {
            return response()->json(['message' => 'Role tidak ditemukan'], 404);
        }

        // Create user baru
        $user = User::create([
            'role_id' => $role->id,
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'no_hp' => $validated['no_hp'] ?? null,
            'alamat' => $validated['alamat'] ?? null
        ]);

        return response()->json([
            'message' => 'User ' . $validated['role'] . ' berhasil ditambahkan',
            'user' => $user
        ], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});

// Endpoint untuk test middleware JWT (profile user)
Route::middleware('jwt')->get('/profile', function (Request $request) {
    return response()->json([
        'message' => 'Profile berhasil diakses',
        'user' => $request->auth_user
    ]);
});

// Endpoint untuk debug token (tanpa middleware)
Route::post('/debug-token', function (Request $request) use ($jwtSecret) {
    $token = $request->bearerToken();

    if (!$token) {
        return response()->json(['message' => 'Token tidak ditemukan'], 400);
    }

    try {
        $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));

        $user = DB::table('users')->where('id', $decoded->sub)->first();

        return response()->json([
            'message' => 'Token valid',
            'decoded' => $decoded,
            'user_exists' => $user ? true : false,
            'user' => $user,
            'current_time' => time(),
            'token_exp' => $decoded->exp,
            'is_expired' => $decoded->exp < time()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Token tidak valid',
            'error' => $e->getMessage()
        ], 401);
    }
});
