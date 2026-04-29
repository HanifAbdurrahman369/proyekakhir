<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;


Route::get('/profile', function (Request $request) {

    if ($request->header('Authorization') != 'Bearer dummy-token-123') {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    return response()->json([
        'name' => 'Hanif',
        'role' => 'Admin'
    ]);
});

Route::post('/find-user', function (Request $request) {

    $request->validate([
        'email' => 'required|email'
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json([
            'message' => 'User tidak ditemukan'
        ], 404);
    }

    return response()->json([
        'id' => $user->id,
        'email' => $user->email,
        'password' => $user->password,
        'role_id' => $user->role_id,
        'nama_lengkap' => $user->nama_lengkap
    ]);
});

Route::post('/register', function (Request $request) {
    try {
        // Validasi request
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'no_hp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string|max:500'
        ]);

        // Ambil role_id untuk petani (otomatis saat registrasi)
        $petaniRole = DB::table('roles')
            ->where('nama_role', 'petani')
            ->first();

        if (!$petaniRole) {
            return response()->json([
                'message' => 'Role petani tidak ditemukan'
            ], 500);
        }

        // Simpan user baru ke database
        $user = User::create([
            'role_id' => $petaniRole->id,
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'no_hp' => $validated['no_hp'] ?? null,
            'alamat' => $validated['alamat'] ?? null
        ]);

        return response()->json([
            'message' => 'Registrasi berhasil sebagai petani',
            'user' => [
                'id' => $user->id,
                'nama_lengkap' => $user->nama_lengkap,
                'email' => $user->email,
                'role' => 'petani',
                'no_hp' => $user->no_hp,
                'alamat' => $user->alamat
            ]
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'message' => 'Validasi gagal',
            'errors' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Terjadi kesalahan pada server',
            'error' => $e->getMessage()
        ], 500);
    }
});
