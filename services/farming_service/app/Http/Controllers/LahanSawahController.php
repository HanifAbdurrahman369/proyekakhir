<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LahanSawah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LahanSawahController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->attributes->get('auth');

        $data = LahanSawah::where('user_id', $user->sub)
            ->select(
                'id',
                'nama_lahan',
                'pemilik_lahan',
                'luas_lahan_hektar',
                'alamat_detail',
                'status_verifikasi'
            )
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
        $data = LahanSawah::with(['kecamatanLahan', 'kelurahan'])
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

    public function show($id)
    {
        $data = LahanSawah::with(['kecamatanLahan', 'kelurahan', 'siklusTanam'])
            ->find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data lahan tidak ditemukan'
            ], 404);
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
            'nama_lahan' => 'required|string|max:100',
            'alamat_detail' => 'required|string|max:150',
        ]);

        $data = LahanSawah::create([
            'user_id' => $user->sub,
            'kecamatan_id' => $request->kecamatan_id,
            'kelurahan_id' => $request->kelurahan_id,
            'nama_lahan' => $request->nama_lahan,
            'alamat_detail' => $request->alamat_detail,

            'status_verifikasi' => 'PENDING',

            'pemilik_lahan' => $request->pemilik_lahan ?? null,
            'tipe_lahan_id' => $request->tipe_lahan_id ?? null,
            'tipe_rawa' => $request->tipe_rawa ?? null,
            'tahun_lbs' => $request->tahun_lbs ?? '2024',
            'luas_lahan_hektar' => $request->luas_lahan_hektar ?? 0,
            'hasil_panen_ton' => 0,
            'produktivitas_ton_ha' => 0,
            'latitude' => $request->latitude ?? null,
            'longitude' => $request->longitude ?? null,
            'foto_lahan' => $request->foto_lahan ?? null,
        ]);

        $this->buatNotifikasiPetugas(
            'Pengajuan Lahan Baru',
            'Petani mengajukan lahan baru: ' . $data->nama_lahan . '. Segera lakukan verifikasi.'
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lahan berhasil dikirim dan menunggu verifikasi petugas',
            'data' => $data
        ], 201);
    }

    public function approve(Request $request, $id)
    {
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

        return response()->json([
            'success' => true,
            'message' => 'Lahan berhasil diterima dan menjadi data legal',
            'data' => $data->fresh()
        ]);
    }

    public function reject(Request $request, $id)
    {
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

        $data->update([
            'status_verifikasi' => 'DITOLAK'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lahan berhasil ditolak',
            'data' => $data->fresh()
        ]);
    }

    private function buatNotifikasiPetugas(string $judul, string $pesan): void
    {
        try {
            DB::table('notifikasi')->insert([
                'role_id_penerima' => 2,
                'user_id_penerima' => null,
                'judul' => $judul,
                'pesan' => $pesan,
                'is_read' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat notifikasi petugas: ' . $e->getMessage());
        }
    }
}