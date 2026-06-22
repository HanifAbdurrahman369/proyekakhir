<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LahanSawah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LahanSawahController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->attributes->get('auth');
        if ((int) ($user->role_id ?? 0) !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Manajemen lahan hanya tersedia untuk Kelompok Tani sebagai pemilik lahan.',
            ], 403);
        }

        $select = [
            'id',
            'pemilik_id',
            'petani_id',
            'kecamatan_id',
            'kelurahan_id',
            'tipe_lahan_id',
            'nama_lahan',
            'luas_lahan_hektar',
            'alamat_detail',
            'status_verifikasi',
        ];

        if (Schema::hasColumn('lahan_sawah', 'alasan_penolakan')) {
            $select[] = 'alasan_penolakan';
        }

        if (Schema::hasColumn('lahan_sawah', 'catatan_verifikasi')) {
            $select[] = 'catatan_verifikasi';
        }

        if (Schema::hasColumn('lahan_sawah', 'polygon_geojson')) {
            $select[] = 'polygon_geojson';
            $select[] = 'status_spasial';
        }

        $data = LahanSawah::with('pemilik:id,nama_lengkap')->where('pemilik_id', $user->sub)
            ->select($select)
            ->orderByRaw("CASE status_verifikasi WHEN 'PENDING' THEN 1 WHEN 'DITOLAK' THEN 2 WHEN 'DITERIMA' THEN 3 ELSE 4 END")
            ->orderByDesc('id')
            ->paginate(2);

        $offset = ($data->currentPage() - 1) * $data->perPage();
        $data->getCollection()->transform(function ($row, $index) use ($offset) {
            $row->nomor_urut = $offset + $index + 1;
            $row->user_id = $row->pemilik_id;
            $row->pemilik_lahan = $row->pemilik?->nama_lengkap;

            return $row;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data lahan berhasil diambil',
            'data' => $data
        ]);
    }

    public function dropdown(Request $request)
    {
        $user = $request->attributes->get('auth');
        $userId = (int) ($user->sub ?? 0);
        $roleId = (int) ($user->role_id ?? 0);

        if (!in_array($roleId, [1, 5], true)) {
            return response()->json(['success' => false, 'message' => 'Akses daftar lahan ditolak.'], 403);
        }

        $query = LahanSawah::where('status_verifikasi', 'DITERIMA');
        if ($roleId === 1) {
            $query->where('pemilik_id', $userId);
        } else {
            $query->where('petani_id', $userId);
        }

        $data = $query->with('pemilik:id,nama_lengkap')
            ->select('id', 'pemilik_id', 'petani_id', 'nama_lahan', 'luas_lahan_hektar')
            ->orderBy('nama_lahan')
            ->get()
            ->values()
            ->map(function ($row, $index) {
                $row->nomor_urut = $index + 1;
                $row->user_id = $row->pemilik_id;
                $row->pemilik_lahan = $row->pemilik?->nama_lengkap;

                return $row;
            });

        return response()->json([
            'success' => true,
            'message' => 'Dropdown lahan legal berhasil diambil',
            'data' => $data
        ]);
    }

    public function accepted()
    {
        $data = LahanSawah::with(['kecamatanLahan', 'kelurahanLahan', 'pemilik:id,nama_lengkap'])
            ->where('status_verifikasi', 'DITERIMA')
            ->select(
                'id',
                'pemilik_id',
                'petani_id',
                'nama_lahan',
                'kecamatan_id',
                'kelurahan_id',
                'luas_lahan_hektar',
                'alamat_detail',
                'status_verifikasi'
            )
            ->orderBy('nama_lahan')
            ->get()
            ->values()
            ->map(function ($row, $index) {
                $row->nomor_urut = $index + 1;
                $row->user_id = $row->pemilik_id;
                $row->pemilik_lahan = $row->pemilik?->nama_lengkap;

                return $row;
            });

        return response()->json([
            'success' => true,
            'message' => 'Data lahan legal berhasil diambil',
            'data' => $data
        ]);
    }

    public function pending()
    {
        $data = LahanSawah::query()
            ->leftJoin('users', 'lahan_sawah.pemilik_id', '=', 'users.id')
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
            ->where('lahan_sawah.status_verifikasi', 'PENDING')
            ->select(
                'lahan_sawah.*',
                'lahan_sawah.pemilik_id as user_id',
                'users.nama_lengkap as nama_petani',
                'users.nama_lengkap as pemilik_lahan',
                'users.email as email_petani',
                'kecamatan.nama_kecamatan',
                'kelurahan.nama_kelurahan'
            )
            ->orderByDesc('lahan_sawah.id')
            ->get()
            ->values()
            ->map(function ($row, $index) {
                $row->nomor_urut = $index + 1;

                return $row;
            });

        return response()->json([
            'success' => true,
            'message' => 'Antrean pengajuan lahan berhasil diambil',
            'data' => $data
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->attributes->get('auth');
        $data = LahanSawah::with(['kecamatanLahan', 'kelurahanLahan', 'pemilik', 'petani'])
            ->find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data lahan tidak ditemukan'
            ], 404);
        }

        $roleId = (int) ($user->role_id ?? $user->role ?? 0);
        $isOwner = (int) $data->pemilik_id === (int) $user->sub;
        $isPrivileged = in_array($roleId, [2, 3, 4], true);
        $isAssignedBrigade = $roleId === 5 && (int) $data->petani_id === (int) $user->sub;

        if (!$isOwner && !$isPrivileged && !$isAssignedBrigade) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke data lahan ini'
            ], 403);
        }

        $data->user_id = $data->pemilik_id;
        $data->pemilik_lahan = $data->pemilik?->nama_lengkap;

        return response()->json([
            'success' => true,
            'message' => 'Detail lahan sawah',
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->attributes->get('auth');
        if ((int) ($user->role_id ?? 0) !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan lahan baru hanya dapat dibuat oleh Kelompok Tani.',
            ], 403);
        }

        $request->validate([
            'kecamatan_id' => 'required',
            'kelurahan_id' => 'required',
            'tipe_lahan_id' => 'required',
            'petani_id' => 'required|integer',
            'nama_lahan' => 'required|string|max:100',
            'alamat_detail' => 'required|string|max:150',
            'luas_lahan_hektar' => 'required|numeric|min:0.01',
        ]);

        $payload = [
            'pemilik_id' => $user->sub,
            'petani_id' => $request->petani_id,
            'kecamatan_id' => $request->kecamatan_id,
            'kelurahan_id' => $request->kelurahan_id,
            'tipe_lahan_id' => $request->tipe_lahan_id,
            'nama_lahan' => $request->nama_lahan,
            'alamat_detail' => $request->alamat_detail,

            'status_verifikasi' => 'PENDING',

            'tahun_lbs' => $request->tahun_lbs ?? '2024',
            'luas_lahan_hektar' => $request->luas_lahan_hektar,
            'hasil_panen_ton' => 0,
            'produktivitas_ton_ha' => 0,
            'latitude' => null,
            'longitude' => null,
            'butuh_bantuan_pemetaan' => false,
            'foto_lahan' => $request->foto_lahan ?? null,
        ];

        if (Schema::hasColumn('lahan_sawah', 'alasan_penolakan')) {
            $payload['alasan_penolakan'] = null;
        }

        if (Schema::hasColumn('lahan_sawah', 'created_at')) {
            $payload['created_at'] = now();
        }

        if (Schema::hasColumn('lahan_sawah', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        $data = LahanSawah::create($payload);

        $this->buatNotifikasiPetugas(
            'Pengajuan Lahan Baru',
            'Petani mengajukan lahan baru: ' . $data->nama_lahan . '. Segera lakukan verifikasi.',
            'lahan_sawah',
            (int) $data->id,
            '/verifikasi-data-petani?lahan_id=' . $data->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lahan berhasil dikirim dan menunggu verifikasi petugas',
            'data' => $data
        ], 201);
    }

    public function approve(Request $request, $id)
    {
        $user = $request->attributes->get('auth');
        $petugasId = $request->input('verified_by') ?? data_get($user, 'sub') ?? data_get($user, 'id');
        $data = LahanSawah::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data lahan tidak ditemukan'
            ], 404);
        }

        if ($data->status_verifikasi === 'DITERIMA') {
            return response()->json([
                'success' => false,
                'message' => 'Lahan sudah diterima sebelumnya'
            ], 400);
        }

        if ($request->filled('petani_id') && !DB::table('users')
            ->where('id', $request->input('petani_id'))
            ->whereIn('role_id', [1, 5])
            ->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Petani penggarap wajib berasal dari Kelompok Tani atau Brigade Pangan.',
            ], 422);
        }

        $updateData = [
            'status_verifikasi' => 'DITERIMA',
        ];

        if (Schema::hasColumn('lahan_sawah', 'alasan_penolakan')) {
            $updateData['alasan_penolakan'] = null;
        }

        if (Schema::hasColumn('lahan_sawah', 'updated_at')) {
            $updateData['updated_at'] = now();
        }

        if ($petugasId && Schema::hasColumn('lahan_sawah', 'verified_by')) {
            $updateData['verified_by'] = (int) $petugasId;
        }

        if (Schema::hasColumn('lahan_sawah', 'verified_at')) {
            $updateData['verified_at'] = now();
        }

        if (Schema::hasColumn('lahan_sawah', 'catatan_verifikasi')) {
            $updateData['catatan_verifikasi'] = 'Pengajuan lahan disetujui oleh petugas dan menunggu proses pemetaan spasial.';
        }

        $fieldOpsional = [
            'petani_id',
            'tipe_lahan_id',
            'tahun_lbs',
            'luas_lahan_hektar',
            'latitude',
            'longitude',
            'foto_lahan',
            'alamat_detail',
        ];

        foreach ($fieldOpsional as $field) {
            if ($request->filled($field)) {
                $updateData[$field] = $request->input($field);
            }
        }

        $data->update($updateData);
        $this->tandaiNotifikasiLahanTerbaca((int) $data->id);

        return response()->json([
            'success' => true,
            'message' => 'Lahan berhasil diterima dan menjadi data legal',
            'data' => $data->fresh()
        ]);
    }

    public function reject(Request $request, $id)
    {
        $user = $request->attributes->get('auth');
        $petugasId = $request->input('verified_by') ?? data_get($user, 'sub') ?? data_get($user, 'id');

        $request->validate([
            'alasan_penolakan' => 'required|string|min:5|max:700',
        ]);

        $data = LahanSawah::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data lahan tidak ditemukan'
            ], 404);
        }

        if ($data->status_verifikasi === 'DITOLAK') {
            return response()->json([
                'success' => false,
                'message' => 'Lahan sudah ditolak sebelumnya'
            ], 400);
        }

        $payload = [
            'status_verifikasi' => 'DITOLAK',
        ];

        if (Schema::hasColumn('lahan_sawah', 'alasan_penolakan')) {
            $payload['alasan_penolakan'] = $request->input('alasan_penolakan');
        }

        if (Schema::hasColumn('lahan_sawah', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        if ($petugasId && Schema::hasColumn('lahan_sawah', 'verified_by')) {
            $payload['verified_by'] = (int) $petugasId;
        }

        if (Schema::hasColumn('lahan_sawah', 'verified_at')) {
            $payload['verified_at'] = now();
        }

        if (Schema::hasColumn('lahan_sawah', 'catatan_verifikasi')) {
            $payload['catatan_verifikasi'] = $request->input('alasan_penolakan');
        }

        $data->update($payload);
        $this->tandaiNotifikasiLahanTerbaca((int) $data->id);

        return response()->json([
            'success' => true,
            'message' => 'Lahan berhasil ditolak',
            'data' => $data->fresh()
        ]);
    }

    public function resubmit(Request $request, $id)
    {
        $user = $request->attributes->get('auth');
        if ((int) ($user->role_id ?? 0) !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Perbaikan pengajuan lahan hanya dapat dilakukan oleh Kelompok Tani.',
            ], 403);
        }

        $request->validate([
            'kecamatan_id' => 'required',
            'kelurahan_id' => 'required',
            'tipe_lahan_id' => 'required',
            'petani_id' => 'required|integer',
            'nama_lahan' => 'required|string|max:100',
            'alamat_detail' => 'required|string|max:150',
            'luas_lahan_hektar' => 'required|numeric|min:0.01',
        ]);

        $data = LahanSawah::where('id', $id)
            ->where('pemilik_id', $user->sub)
            ->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data lahan tidak ditemukan'
            ], 404);
        }

        if ($data->status_verifikasi !== 'DITOLAK') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pengajuan yang ditolak yang dapat diajukan ulang'
            ], 400);
        }

        $payload = [
            'kecamatan_id' => $request->kecamatan_id,
            'kelurahan_id' => $request->kelurahan_id,
            'tipe_lahan_id' => $request->tipe_lahan_id,
            'petani_id' => $request->petani_id,
            'nama_lahan' => $request->nama_lahan,
            'alamat_detail' => $request->alamat_detail,
            'tahun_lbs' => $request->tahun_lbs ?? '2024',
            'luas_lahan_hektar' => $request->luas_lahan_hektar,
            'status_verifikasi' => 'PENDING',
            'alasan_penolakan' => null,
            'butuh_bantuan_pemetaan' => false,
        ];

        if (Schema::hasColumn('lahan_sawah', 'alasan_penolakan')) {
            $payload['alasan_penolakan'] = null;
        }

        if (Schema::hasColumn('lahan_sawah', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        if (Schema::hasColumn('lahan_sawah', 'verified_by')) {
            $payload['verified_by'] = null;
        }

        if (Schema::hasColumn('lahan_sawah', 'verified_at')) {
            $payload['verified_at'] = null;
        }

        if (Schema::hasColumn('lahan_sawah', 'catatan_verifikasi')) {
            $payload['catatan_verifikasi'] = null;
        }

        $data->update($payload);

        $this->buatNotifikasiPetugas(
            'Pengajuan Ulang Lahan',
            'Petani memperbaiki dan mengajukan ulang lahan: ' . $data->fresh()->nama_lahan . '. Segera lakukan verifikasi.',
            'lahan_sawah',
            (int) $data->id,
            '/verifikasi-data-petani?lahan_id=' . $data->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lahan berhasil diperbaiki dan dikirim ulang',
            'data' => $data->fresh()
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->attributes->get('auth');
        if ((int) ($user->role_id ?? 0) !== 1) {
            return response()->json(['success' => false, 'message' => 'Penghapusan lahan hanya tersedia untuk Kelompok Tani.'], 403);
        }

        $data = LahanSawah::where('id', $id)->where('pemilik_id', $user->sub)->first();
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Pengajuan lahan tidak ditemukan.'], 404);
        }

        if ($data->status_verifikasi === 'DITERIMA') {
            return response()->json([
                'success' => false,
                'message' => 'Lahan yang sudah diterima menjadi data legal dan tidak dapat dihapus oleh pemilik.',
            ], 422);
        }

        $this->tandaiNotifikasiLahanTerbaca((int) $data->id);
        $data->delete();

        return response()->json(['success' => true, 'message' => 'Pengajuan lahan berhasil dihapus.']);
    }

    private function buatNotifikasiPetugas(string $judul, string $pesan, ?string $refType = null, ?int $refId = null, ?string $targetUrl = null): void
    {
        try {
            $payload = [
                'role_id_penerima' => 2,
                'user_id_penerima' => null,
                'judul' => $judul,
                'pesan' => $pesan,
                'is_read' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('notifikasi', 'ref_type')) {
                $payload['ref_type'] = $refType;
            }

            if (Schema::hasColumn('notifikasi', 'ref_id')) {
                $payload['ref_id'] = $refId;
            }

            if (Schema::hasColumn('notifikasi', 'target_url')) {
                $payload['target_url'] = $targetUrl ?: '/verifikasi-data-petani';
            }

            DB::table('notifikasi')->insert($payload);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat notifikasi petugas: ' . $e->getMessage());
        }
    }

    private function tandaiNotifikasiLahanTerbaca(int $lahanId): void
    {
        try {
            if (Schema::hasColumn('notifikasi', 'ref_type') && Schema::hasColumn('notifikasi', 'ref_id')) {
                DB::table('notifikasi')
                    ->where('ref_type', 'lahan_sawah')
                    ->where('ref_id', $lahanId)
                    ->update([
                        'is_read' => 1,
                        'updated_at' => now(),
                    ]);
            }

            if (Schema::hasColumn('notifikasi', 'target_url')) {
                DB::table('notifikasi')
                    ->where('target_url', 'like', '%lahan_id=' . $lahanId . '%')
                    ->update([
                        'is_read' => 1,
                        'updated_at' => now(),
                    ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal menandai notifikasi lahan terbaca: ' . $e->getMessage());
        }
    }
}
