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
        $select = [
            'id',
            'user_id',
            'kecamatan_id',
            'kelurahan_id',
            'tipe_lahan_id',
            'nama_lahan',
            'pemilik_lahan',
            'luas_lahan_hektar',
            'alamat_detail',
            'status_verifikasi',
        ];

        if (Schema::hasColumn('lahan_sawah', 'alasan_penolakan')) {
            $select[] = 'alasan_penolakan';
        }

        $data = LahanSawah::where('user_id', $user->sub)
            ->select($select)
            ->orderByDesc('id')
            ->paginate(5);

        return response()->json([
            'success' => true,
            'message' => 'Data lahan berhasil diambil',
            'data' => $data
        ]);
    }

    public function dropdown(Request $request)
    {
        $user = $request->attributes->get('auth');

        $data = LahanSawah::where('user_id', $user->sub)
            ->where('status_verifikasi', 'DITERIMA')
            ->select('id', 'nama_lahan')
            ->orderBy('nama_lahan')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Dropdown lahan legal berhasil diambil',
            'data' => $data
        ]);
    }

    public function accepted()
    {
        $data = LahanSawah::with(['kecamatanLahan', 'kelurahanLahan'])
            ->where('status_verifikasi', 'DITERIMA')
            ->select(
                'id',
                'user_id',
                'nama_lahan',
                'pemilik_lahan',
                'kecamatan_id',
                'kelurahan_id',
                'luas_lahan_hektar',
                'alamat_detail',
                'status_verifikasi'
            )
            ->orderBy('nama_lahan')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data lahan legal berhasil diambil',
            'data' => $data
        ]);
    }

    public function pending()
    {
        $data = LahanSawah::query()
            ->leftJoin('users', 'lahan_sawah.user_id', '=', 'users.id')
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
            ->where('lahan_sawah.status_verifikasi', 'PENDING')
            ->select(
                'lahan_sawah.*',
                'users.nama_lengkap as nama_petani',
                'users.email as email_petani',
                'kecamatan.nama_kecamatan',
                'kelurahan.nama_kelurahan'
            )
            ->orderByDesc('lahan_sawah.id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Antrean pengajuan lahan berhasil diambil',
            'data' => $data
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->attributes->get('auth');
        $data = LahanSawah::with(['kecamatanLahan', 'kelurahanLahan'])
            ->find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data lahan tidak ditemukan'
            ], 404);
        }

        $roleId = (int) ($user->role_id ?? $user->role ?? 0);
        $isOwner = (int) $data->user_id === (int) $user->sub;
        $isPrivileged = in_array($roleId, [2, 3, 4], true);

        if (!$isOwner && !$isPrivileged) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke data lahan ini'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail lahan sawah',
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->attributes->get('auth');

        $request->validate([
            'kecamatan_id' => 'required',
            'kelurahan_id' => 'required',
            'tipe_lahan_id' => 'required',
            'nama_lahan' => 'required|string|max:100',
            'alamat_detail' => 'required|string|max:150',
        ]);

        $payload = [
            'user_id' => $user->sub,
            'kecamatan_id' => $request->kecamatan_id,
            'kelurahan_id' => $request->kelurahan_id,
            'tipe_lahan_id' => $request->tipe_lahan_id,
            'nama_lahan' => $request->nama_lahan,
            'alamat_detail' => $request->alamat_detail,

            'status_verifikasi' => 'PENDING',

            'pemilik_lahan' => $request->pemilik_lahan ?? null,
            'tahun_lbs' => $request->tahun_lbs ?? '2024',
            'luas_lahan_hektar' => $request->luas_lahan_hektar ?? 0,
            'hasil_panen_ton' => 0,
            'produktivitas_ton_ha' => 0,
            'latitude' => $request->latitude ?? null,
            'longitude' => $request->longitude ?? null,
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
            'pemilik_lahan',
            'tipe_lahan_id',
            'tipe_rawa',
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

        $request->validate([
            'kecamatan_id' => 'required',
            'kelurahan_id' => 'required',
            'tipe_lahan_id' => 'required',
            'nama_lahan' => 'required|string|max:100',
            'alamat_detail' => 'required|string|max:150',
            'pemilik_lahan' => 'nullable|string|max:100',
        ]);

        $data = LahanSawah::where('id', $id)
            ->where('user_id', $user->sub)
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
            'pemilik_lahan' => $request->pemilik_lahan ?? null,
            'status_verifikasi' => 'PENDING',
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
