<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Models\User;

class UserController extends Controller
{
    /*
    =====================================
    PROFILE
    =====================================
    */

    public function profile(Request $request)
    {
        if (
            $request->header('Authorization')
            != 'Bearer dummy-token-123'
        ) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        return response()->json([
            'name' => 'Hanif',
            'role' => 'Admin'
        ]);
    }

    /*
    =====================================
    FIND USER
    =====================================
    */

    public function findUser(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where(
            'email',
            $request->email
        )->first();

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
    }

    /*
    =====================================
    REGISTER
    =====================================
    */

    public function register(Request $request)
    {
        try {
            /*
            =====================================
            VALIDASI REQUEST
            =====================================
            */

            $validated = $request->validate([
                'nama_lengkap' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                'no_hp' => 'nullable|string|max:20',
                'alamat' => 'nullable|string|max:500'
            ]);

            /*
            =====================================
            AMBIL ROLE PETANI
            =====================================
            */

            $petaniRole = DB::table('roles')
                ->where('nama_role', 'petani')
                ->first();

            if (!$petaniRole) {
                return response()->json([
                    'message' => 'Role petani tidak ditemukan'
                ], 500);
            }

            /*
            =====================================
            SIMPAN USER BARU
            =====================================
            */

            $user = User::create([
                'role_id' => $petaniRole->id,
                'nama_lengkap' => $validated['nama_lengkap'],
                'email' => $validated['email'],
                'password' => Hash::make(
                    $validated['password']
                ),
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
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

    $status = Password::sendResetLink(
        $request->only('email')
    );

    if ($status === Password::RESET_LINK_SENT) {

        return response()->json([
            'message' => 'Link reset password berhasil dikirim'
        ]);
    }

    return response()->json([
        'message' => 'Email tidak ditemukan'
    ], 404);
}

public function resetPassword(Request $request)
{
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:6|confirmed',
    ]);

    $status = Password::reset(
        $request->only(
            'email',
            'password',
            'password_confirmation',
            'token'
        ),
        function ($user, $password) {

            $user->password = Hash::make($password);

            $user->save();
        }
    );

    if ($status == Password::PASSWORD_RESET) {

        return response()->json([
            'message' => 'Password berhasil direset'
        ]);
    }

    return response()->json([
        'message' => 'Token tidak valid'
    ], 400);
}
/*
    =====================================
    FULL CRUD MANAJEMEN PENGGUNA (ADMIN)
    =====================================
    */

    // 1. Tampilkan Semua User
    public function index()
    {
        // Mengambil semua user dan mengurutkan dari yang terbaru
        $users = DB::table('users')->orderBy('id', 'desc')->get();
        return response()->json(['data' => $users]);
    }

    public function store(Request $request)
    {
        // Cek manual jika email sudah ada agar errornya jelas
        $exists = DB::table('users')->where('email', $request->email)->first();
        if ($exists) {
            return response()->json(['message' => 'Email ini sudah digunakan oleh pengguna lain.'], 422);
        }

        DB::table('users')->insert([
            'nama_lengkap' => $request->nama_lengkap,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Wajib hash password
            'role_id' => $request->role_id,
            'no_hp' => $request->no_hp ?? null,
            'alamat' => $request->alamat ?? null,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'User berhasil dibuat'], 201);
    }

    public function show($id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) return response()->json(['message' => 'User tidak ditemukan'], 404);
        
        return response()->json(['data' => $user]);
    }

    public function update(Request $request, $id)
    {
        $data = [
            'nama_lengkap' => $request->nama_lengkap,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'no_hp' => $request->no_hp ?? null,
            'alamat' => $request->alamat ?? null,
            'updated_at' => now()
        ];

        // Jika form password diisi, ubah passwordnya
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        DB::table('users')->where('id', $id)->update($data);

        return response()->json(['message' => 'User berhasil diupdate']);
    }

    public function destroy($id)
    {
        DB::table('users')->where('id', $id)->delete();
        return response()->json(['message' => 'User berhasil dihapus']);
    }
}