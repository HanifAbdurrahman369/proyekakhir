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
        $roleId = (int) ($user->role_id ?? 0);
        if (!in_array($roleId, [1, 5], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Manajemen lahan hanya tersedia untuk Kelompok Tani atau Brigade Pangan.',
            ], 403);
        }

        $select = [
            'id',
            'pemilik_id',

            'kecamatan_id',
            'kelurahan_id',
            'tipe_lahan_id',
            'nama_lahan',
            'luas_lahan_hektar',
            'alamat_detail',
            'status_verifikasi',
        ];

        if (Schema::hasColumn('lahan_sawah', 'assigned_petugas_id')) {
            $select[] = 'assigned_petugas_id';
        }

        if (Schema::hasColumn('lahan_sawah', 'luas_tanam_hektar')) {
            $select[] = 'luas_tanam_hektar';
        }

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

        $query = LahanSawah::with(['pemilik:id,nama_lengkap', 'kecamatanLahan:id,nama_kecamatan']);
        if (in_array($roleId, [1, 5], true)) {
            $query->where('pemilik_id', $user->sub);
        } else {
            // ...
        }

        $data = $query->select($select)
            ->orderByRaw("CASE status_verifikasi WHEN 'PENDING' THEN 1 WHEN 'DITOLAK' THEN 2 WHEN 'DITERIMA' THEN 3 ELSE 4 END")
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 2));

        $offset = ($data->currentPage() - 1) * $data->perPage();
        $data->getCollection()->transform(function ($row, $index) use ($offset) {
            $row->nomor_urut = $offset + $index + 1;
            $row->user_id = $row->pemilik_id;
            $row->pemilik_lahan = $row->pemilik?->nama_lengkap;
            $row->nama_kecamatan = $row->kecamatanLahan?->nama_kecamatan;

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
        if (Schema::hasColumn('lahan_sawah', 'status_spasial')) {
            $query->where('status_spasial', 'SUDAH_DIPETAKAN');
        }

        if (in_array($roleId, [1, 5], true)) {
            $query->where('pemilik_id', $userId);
        } else {
            // ...
        }

        $select = ['id', 'pemilik_id', 'nama_lahan', 'luas_lahan_hektar'];
        if (Schema::hasColumn('lahan_sawah', 'luas_tanam_hektar')) {
            $select[] = 'luas_tanam_hektar';
        }
        if (Schema::hasColumn('lahan_sawah', 'status_spasial')) {
            $select[] = 'status_spasial';
        }

        $data = $query->with('pemilik:id,nama_lengkap')
            ->select($select)
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

    public function accepted(Request $request)
    {
        $select = [
            'id',
            'pemilik_id',

            'nama_lahan',
            'kecamatan_id',
            'kelurahan_id',
            'tipe_lahan_id',
            'luas_lahan_hektar',
            'alamat_detail',
            'status_verifikasi',
        ];

        if (Schema::hasColumn('lahan_sawah', 'assigned_petugas_id')) {
            $select[] = 'assigned_petugas_id';
        }

        if (Schema::hasColumn('lahan_sawah', 'luas_tanam_hektar')) {
            $select[] = 'luas_tanam_hektar';
        }

        $query = LahanSawah::with(['kecamatanLahan', 'kelurahanLahan', 'tipeLahan', 'pemilik:id,nama_lengkap'])
            ->where('status_verifikasi', 'DITERIMA')
            ->select($select);

        $this->applyPetugasWilayahScope($query, $request);

        $data = $query
            ->orderBy('nama_lahan')
            ->get()
            ->values()
            ->map(function ($row, $index) {
                $row->nomor_urut = $index + 1;
                $row->user_id = $row->pemilik_id;
                $row->pemilik_lahan = $row->pemilik?->nama_lengkap;
                $row->nama_kecamatan = $row->kecamatanLahan?->nama_kecamatan;
                $row->nama_kelurahan = $row->kelurahanLahan?->nama_kelurahan;
                $row->nama_tipe_lahan = $row->tipeLahan?->nama_tipe;

                return $row;
            });

        return response()->json([
            'success' => true,
            'message' => 'Data lahan legal berhasil diambil',
            'data' => $data
        ]);
    }

    public function pending(Request $request)
    {
        $query = LahanSawah::query()
            ->leftJoin('users', 'lahan_sawah.pemilik_id', '=', 'users.id')
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
            ->where('lahan_sawah.status_verifikasi', 'PENDING');

        $this->applyPetugasWilayahScope($query, $request, 'lahan_sawah');

        $data = $query
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
        $isAssignedBrigade = false; // No longer applies

        if ($roleId === 2 && !$this->petugasBolehMelihatLahan($request, $data)) {
            return response()->json([
                'success' => false,
                'message' => 'Lahan ini berada di luar wilayah kerja petugas.',
            ], 403);
        }

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
        if (!in_array((int) ($user->role_id ?? 0), [1, 5], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan lahan baru hanya dapat dibuat oleh Kelompok Tani atau Brigade Pangan.',
            ], 403);
        }

        $request->validate([
            'kecamatan_id' => 'required',
            'kelurahan_id' => 'required',
            'tipe_lahan_id' => 'required',

            'nama_lahan' => 'required|string|max:100',
            'alamat_detail' => 'required|string|max:150',
            'luas_lahan_hektar' => 'required|numeric|min:0.01',
            'luas_tanam_hektar' => 'nullable|numeric|min:0.01',
        ]);

        $luasLahan = (float) $request->luas_lahan_hektar;
        $luasTanam = (float) ($request->input('luas_tanam_hektar') ?: $luasLahan);
        if ($luasTanam > $luasLahan) {
            return response()->json([
                'success' => false,
                'message' => 'Luas tanam tidak boleh lebih besar dari luas lahan.',
            ], 422);
        }

        $assignedPetugasId = $this->findAssignedPetugasId((int) $request->kecamatan_id, (int) $request->kelurahan_id);

        $payload = [
            'pemilik_id' => $user->sub,

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
        ];

        if (Schema::hasColumn('lahan_sawah', 'assigned_petugas_id')) {
            $payload['assigned_petugas_id'] = $assignedPetugasId;
        }

        if (Schema::hasColumn('lahan_sawah', 'luas_tanam_hektar')) {
            $payload['luas_tanam_hektar'] = $luasTanam;
        }

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
            '/verifikasi-data-petani?lahan_id=' . $data->id,
            $assignedPetugasId
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

        if ((int) ($user->role_id ?? $user->role ?? 0) === 2 && !$this->petugasBolehMelihatLahan($request, $data)) {
            return response()->json([
                'success' => false,
                'message' => 'Lahan ini berada di luar wilayah kerja petugas.',
            ], 403);
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

            'kecamatan_id',
            'kelurahan_id',
            'tipe_lahan_id',
            'tahun_lbs',
            'luas_lahan_hektar',
            'luas_tanam_hektar',
            'latitude',
            'longitude',
            'alamat_detail',
        ];

        foreach ($fieldOpsional as $field) {
            if ($request->filled($field)) {
                $updateData[$field] = $request->input($field);
            }
        }

        $luasLahan = (float) ($updateData['luas_lahan_hektar'] ?? $data->luas_lahan_hektar);
        $luasTanam = (float) ($updateData['luas_tanam_hektar'] ?? $data->luas_tanam_hektar ?? $luasLahan);
        if ($luasTanam > $luasLahan) {
            return response()->json([
                'success' => false,
                'message' => 'Luas tanam tidak boleh lebih besar dari luas lahan.',
            ], 422);
        }

        if (Schema::hasColumn('lahan_sawah', 'luas_tanam_hektar')) {
            $updateData['luas_tanam_hektar'] = $luasTanam;
        }

        if (Schema::hasColumn('lahan_sawah', 'assigned_petugas_id')) {
            $updateData['assigned_petugas_id'] = $this->findAssignedPetugasId(
                (int) ($updateData['kecamatan_id'] ?? $data->kecamatan_id),
                (int) ($updateData['kelurahan_id'] ?? $data->kelurahan_id)
            );
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

        if ((int) ($user->role_id ?? $user->role ?? 0) === 2 && !$this->petugasBolehMelihatLahan($request, $data)) {
            return response()->json([
                'success' => false,
                'message' => 'Lahan ini berada di luar wilayah kerja petugas.',
            ], 403);
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
        if (!in_array((int) ($user->role_id ?? 0), [1, 5], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Perbaikan pengajuan lahan hanya dapat dilakukan oleh Kelompok Tani atau Brigade Pangan.',
            ], 403);
        }

        $request->validate([
            'kecamatan_id' => 'required',
            'kelurahan_id' => 'required',
            'tipe_lahan_id' => 'required',

            'nama_lahan' => 'required|string|max:100',
            'alamat_detail' => 'required|string|max:150',
            'luas_lahan_hektar' => 'required|numeric|min:0.01',
            'luas_tanam_hektar' => 'nullable|numeric|min:0.01',
        ]);

        $luasLahan = (float) $request->luas_lahan_hektar;
        $luasTanam = (float) ($request->input('luas_tanam_hektar') ?: $luasLahan);
        if ($luasTanam > $luasLahan) {
            return response()->json([
                'success' => false,
                'message' => 'Luas tanam tidak boleh lebih besar dari luas lahan.',
            ], 422);
        }

        $assignedPetugasId = $this->findAssignedPetugasId((int) $request->kecamatan_id, (int) $request->kelurahan_id);

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

            'nama_lahan' => $request->nama_lahan,
            'alamat_detail' => $request->alamat_detail,
            'tahun_lbs' => $request->tahun_lbs ?? '2024',
            'luas_lahan_hektar' => $request->luas_lahan_hektar,
            'status_verifikasi' => 'PENDING',
            'alasan_penolakan' => null,
        ];

        if (Schema::hasColumn('lahan_sawah', 'assigned_petugas_id')) {
            $payload['assigned_petugas_id'] = $assignedPetugasId;
        }

        if (Schema::hasColumn('lahan_sawah', 'luas_tanam_hektar')) {
            $payload['luas_tanam_hektar'] = $luasTanam;
        }

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
            '/verifikasi-data-petani?lahan_id=' . $data->id,
            $assignedPetugasId
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
        if (!in_array((int) ($user->role_id ?? 0), [1, 5], true)) {
            return response()->json(['success' => false, 'message' => 'Penghapusan lahan hanya tersedia untuk Kelompok Tani atau Brigade Pangan.'], 403);
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

    private function buatNotifikasiPetugas(string $judul, string $pesan, ?string $refType = null, ?int $refId = null, ?string $targetUrl = null, ?int $userIdPenerima = null): void
    {
        try {
            $payload = [
                'role_id_penerima' => 2,
                'user_id_penerima' => $userIdPenerima,
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

            if ($refType && $refId && Schema::hasColumn('notifikasi', 'ref_type') && Schema::hasColumn('notifikasi', 'ref_id')) {
                DB::table('notifikasi')->updateOrInsert([
                    'role_id_penerima' => 2,
                    'user_id_penerima' => $userIdPenerima,
                    'ref_type' => $refType,
                    'ref_id' => $refId,
                ], $payload);
            } else {
                DB::table('notifikasi')->insert($payload);
            }
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
                    ->delete();
            }

            if (Schema::hasColumn('notifikasi', 'target_url')) {
                DB::table('notifikasi')
                    ->where('target_url', 'like', '%lahan_id=' . $lahanId . '%')
                    ->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal menandai notifikasi lahan terbaca: ' . $e->getMessage());
        }
    }

    private function findAssignedPetugasId(?int $kecamatanId, ?int $kelurahanId): ?int
    {
        $petugas = DB::table('users')
            ->join('komunitas', 'users.komunitas_id', '=', 'komunitas.id')
            ->where('users.role_id', 2)
            ->select('users.id', 'komunitas.wilayah_kecamatan_id', 'komunitas.wilayah_kelurahan_ids')
            ->orderBy('users.id')
            ->get();

        foreach ($petugas as $row) {
            if ($kelurahanId && in_array($kelurahanId, $this->kelurahanIds($row->wilayah_kelurahan_ids ?? null), true)) {
                return (int) $row->id;
            }
        }

        foreach ($petugas as $row) {
            if ($kecamatanId && (int) ($row->wilayah_kecamatan_id ?? 0) === $kecamatanId) {
                return (int) $row->id;
            }
        }

        return null;
    }

    private function applyPetugasWilayahScope($query, Request $request, string $table = 'lahan_sawah'): void
    {
        $user = $request->attributes->get('auth');
        if ((int) ($user->role_id ?? $user->role ?? 0) !== 2) {
            return;
        }

        $wilayah = $this->petugasWilayah((int) ($user->sub ?? $user->id ?? 0));
        if (!empty($wilayah['kelurahan_ids'])) {
            $query->whereIn($table . '.kelurahan_id', $wilayah['kelurahan_ids']);
            return;
        }

        if ($wilayah['kecamatan_id']) {
            $query->where($table . '.kecamatan_id', $wilayah['kecamatan_id']);
        }
    }

    private function petugasBolehMelihatLahan(Request $request, LahanSawah $lahan): bool
    {
        $user = $request->attributes->get('auth');
        $wilayah = $this->petugasWilayah((int) ($user->sub ?? $user->id ?? 0));

        if (empty($wilayah['kelurahan_ids']) && !$wilayah['kecamatan_id']) {
            return true;
        }

        if (!empty($wilayah['kelurahan_ids'])) {
            return in_array((int) $lahan->kelurahan_id, $wilayah['kelurahan_ids'], true);
        }

        return (int) $lahan->kecamatan_id === (int) $wilayah['kecamatan_id'];
    }

    private function petugasWilayah(int $userId): array
    {
        $petugas = DB::table('users')
            ->join('komunitas', 'users.komunitas_id', '=', 'komunitas.id')
            ->where('users.id', $userId)
            ->where('users.role_id', 2)
            ->select('komunitas.wilayah_kecamatan_id', 'komunitas.wilayah_kelurahan_ids')
            ->first();

        return [
            'kecamatan_id' => $petugas?->wilayah_kecamatan_id ? (int) $petugas->wilayah_kecamatan_id : null,
            'kelurahan_ids' => $this->kelurahanIds($petugas?->wilayah_kelurahan_ids ?? null),
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
