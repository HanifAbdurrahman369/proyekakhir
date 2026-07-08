<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
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
            'komunitas_id' => $user->komunitas_id,
            'nama_lengkap' => $user->nama_lengkap,
            'no_hp' => $user->no_hp,
            'alamat' => $user->alamat,
            'wilayah_kecamatan_id' => $user->wilayah_kecamatan_id,
            'wilayah_kelurahan_ids' => $this->kelurahanIds($user->wilayah_kelurahan_ids ?? null),
            'instansi_asal' => $user->instansi_asal,
            'nama_bpp' => $user->nama_bpp,
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
                'jenis_kelompok' => 'required|in:kelompok_tani,brigade_pangan',
            ], [
                'jenis_kelompok.required' => 'Silakan pilih Kelompok Tani atau Brigade Pangan.',
                'jenis_kelompok.in' => 'Pilihan keanggotaan tidak valid.',
            ]);

            $komunitas = DB::table('komunitas')
                ->whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower(trim($validated['nama_lengkap']))])
                ->where('jenis_komunitas', $validated['jenis_kelompok'])
                ->where('status_keanggotaan', 'AKTIF')
                ->first();

            if (!$komunitas) {
                return response()->json([
                    'message' => 'Mohon maaf, data Anda belum terdaftar pada database Kelompok Tani atau Brigade Pangan. Silakan hubungi petugas untuk memastikan pendataan keanggotaan terlebih dahulu.',
                    'errors' => [
                        'nama_lengkap' => [
                            'Data petani tidak ditemukan pada kategori keanggotaan yang dipilih.'
                        ],
                    ],
                ], 422);
            }

            /*
            =====================================
            AMBIL ROLE PETANI
            =====================================
            */

            $role = DB::table('roles')
                ->where('nama_role', $validated['jenis_kelompok'])
                ->first();

            if (!$role) {
                return response()->json([
                    'message' => 'Role keanggotaan petani tidak ditemukan'
                ], 500);
            }

            /*
            =====================================
            SIMPAN USER BARU
            =====================================
            */

            $user = User::create([
                'role_id' => $role->id,
                'komunitas_id' => $komunitas->id,
                'nama_lengkap' => $validated['nama_lengkap'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'no_hp' => $komunitas->nomor_hp ?? null,
                'alamat' => $komunitas->alamat ?? null
            ]);

            return response()->json([
                'message' => 'Registrasi berhasil sebagai ' . str_replace('_', ' ', $validated['jenis_kelompok']),
                'user' => [
                    'id' => $user->id,
                    'nama_lengkap' => $user->nama_lengkap,
                    'email' => $user->email,
                    'role_id' => (int) $role->id,
                    'role' => $validated['jenis_kelompok'],
                    'no_hp' => $user->no_hp,
                    'alamat' => $user->alamat,
                    'komunitas' => [
                        'id' => $komunitas->id,
                        'jenis' => $validated['jenis_kelompok'],
                        'nama' => $komunitas->nama,
                        'nama_komunitas' => $komunitas->nama_komunitas,
                    ],
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

        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'message' => 'Mohon tunggu beberapa saat sebelum meminta link reset kembali (throttled).'
            ], 429);
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

            $user->password = $password;

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
        $users = DB::table('users')->orderBy('id', 'desc')->get()
            ->map(fn ($user) => $this->formatUser($user))
            ->values();

        return response()->json(['data' => $users]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedAdminUser($request);
        $payload = $this->payloadAdminUser($validated);

        if (in_array((int)$validated['role_id'], [1, 2, 5])) {
            $payload['komunitas_id'] = $this->createOrUpdateKomunitas($validated);
        }

        DB::table('users')->insert(array_merge($payload, [
            'password' => Hash::make($validated['password']),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return response()->json(['message' => 'User berhasil dibuat'], 201);
    }

    public function show($id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) return response()->json(['message' => 'User tidak ditemukan'], 404);
        
        return response()->json(['data' => $this->formatUser($user)]);
    }

    public function update(Request $request, $id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        $validated = $this->validatedAdminUser($request, (int) $id);
        $data = $this->payloadAdminUser($validated);
        $data['updated_at'] = now();

        if (in_array((int)$validated['role_id'], [1, 2, 5])) {
            $data['komunitas_id'] = $this->createOrUpdateKomunitas($validated, $user->komunitas_id);
        } else {
            $data['komunitas_id'] = null;
        }

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

    private function validatedAdminUser(Request $request, ?int $id = null): array
    {
        $rules = [
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($id)],
            'role_id' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'password' => [$id ? 'nullable' : 'required', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => [$id ? 'nullable' : 'required_with:password', 'string', 'min:6'],
            'komunitas_id' => ['nullable', 'integer'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
            'nik' => ['nullable', 'string', 'max:20'],
            'wilayah_kecamatan_id' => ['nullable', 'integer', 'exists:kecamatan,id'],
            'wilayah_kelurahan_ids' => ['nullable', 'array'],
            'wilayah_kelurahan_ids.*' => ['integer', 'exists:kelurahan,id'],
            'instansi_asal' => ['nullable', Rule::in(['DINAS_PERTANIAN', 'BPP'])],
            'nama_bpp' => ['nullable', 'string', 'max:120'],
        ];

        $validated = $request->validate($rules);
        $roleId = (int) ($validated['role_id'] ?? 0);

        if ($roleId === 2) {
            // Petugas no longer has these fields directly on their user record.
            // They belong to a komunitas (BPP/Brigade) which has these assigned.
        }

        return $validated;
    }

    private function payloadAdminUser(array $validated): array
    {
        $roleId = (int) $validated['role_id'];
        return [
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'],
            'role_id' => $roleId,
            'no_hp' => $validated['no_hp'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
        ];
    }

    private function createOrUpdateKomunitas(array $validated, ?int $komunitasId = null): int
    {
        $roleId = (int) $validated['role_id'];
        $jenisKomunitas = '';
        $namaKomunitas = '';
        
        if ($roleId === 1) {
            $jenisKomunitas = 'kelompok_tani';
            $namaKomunitas = $validated['nama_lengkap'];
        } elseif ($roleId === 5) {
            $jenisKomunitas = 'brigade_pangan';
            $namaKomunitas = $validated['nama_lengkap'];
        } elseif ($roleId === 2) {
            $jenisKomunitas = 'BPP';
            $kecamatanName = DB::table('kecamatan')->where('id', $validated['wilayah_kecamatan_id'] ?? 0)->value('nama_kecamatan');
            $namaKomunitas = 'BPP ' . ($kecamatanName ?? '');
        }

        $data = [
            'jenis_komunitas' => $jenisKomunitas,
            'nama' => $validated['nama_lengkap'],
            'nama_komunitas' => $namaKomunitas,
            'nik' => $validated['nik'] ?? null,
            'nomor_hp' => $validated['no_hp'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
            'wilayah_kecamatan_id' => $validated['wilayah_kecamatan_id'] ?? null,
            'wilayah_kelurahan_ids' => isset($validated['wilayah_kelurahan_ids']) ? json_encode($this->kelurahanIds($validated['wilayah_kelurahan_ids'])) : null,
            'updated_at' => now(),
        ];

        if ($roleId === 2) {
            $data['instansi_asal'] = $validated['instansi_asal'] ?? null;
            $data['nama_bpp'] = $validated['nama_bpp'] ?? null;
        }

        if ($komunitasId && DB::table('komunitas')->where('id', $komunitasId)->exists()) {
            DB::table('komunitas')->where('id', $komunitasId)->update($data);
            return $komunitasId;
        }

        $data['created_at'] = now();
        return DB::table('komunitas')->insertGetId($data);
    }

    private function formatUser(object $user): array
    {
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
            'role_id' => $user->role_id !== null ? (int) $user->role_id : null,
            'komunitas_id' => $user->komunitas_id !== null ? (int) $user->komunitas_id : null,
            'komunitas_nama' => $komunitas->nama_komunitas ?? $komunitas->nama ?? null,
            'komunitas_jenis' => $komunitas->jenis_komunitas ?? null,
            'nama_lengkap' => $user->nama_lengkap,
            'email' => $user->email,
            'no_hp' => $user->no_hp ?? null,
            'alamat' => $user->alamat ?? null,
            'nik' => $komunitas->nik ?? null,
            'wilayah_kecamatan_id' => $komunitas->wilayah_kecamatan_id ?? null,
            'wilayah_kecamatan_nama' => ($komunitas && $komunitas->wilayah_kecamatan_id)
                ? DB::table('kecamatan')->where('id', $komunitas->wilayah_kecamatan_id)->value('nama_kecamatan')
                : null,
            'wilayah_kelurahan_ids' => $kelurahanIds,
            'wilayah_kelurahan_nama' => $kelurahanNames,
            'instansi_asal' => $komunitas->instansi_asal ?? null,
            'nama_bpp' => $komunitas->nama_bpp ?? null,
            'created_at' => $user->created_at ?? null,
            'updated_at' => $user->updated_at ?? null,
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
