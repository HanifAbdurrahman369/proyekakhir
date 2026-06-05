<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LahanSawah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LahanSawahController extends Controller
{
    /**
     * Menampilkan semua data lahan sawah
     * termasuk polygon GIS dalam format GeoJSON
     */
 public function index(Request $request)
{
    $user = $request->attributes->get('auth');

    $data = LahanSawah::where('user_id', $user->sub)
            ->where('status_verifikasi', 'DITERIMA')
            ->select(
                'id',
                'nama_lahan'
            )
            ->get();

    return response()->json([
        'success' => true,
        'message' => 'Data lahan berhasil diambil',
        'data' => $data
    ]);
}

    /**
     * Menampilkan detail lahan sawah
     */
    public function show($id)
    {
        $data = LahanSawah::selectRaw("
            id,
            nama_lahan,
            pemilik_lahan,
            luas_lahan_hektar,
            hasil_panen_ton,
            produktivitas_ton_ha,
            ST_AsGeoJSON(polygon_area) as polygon_area
        ")->find($id);

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
        ], 200);
    }

    public function store(Request $request)
    {
        $user = $request->attributes->get('auth');

        $request->validate([
            'kecamatan_id' => 'required',
            'kelurahan_id' => 'required',
            'nama_lahan' => 'required',
            'alamat_detail' => 'required',
        ]);

        $data = LahanSawah::create([
            'user_id' => $user->sub,

            // dari petani
            'kecamatan_id' => $request->kecamatan_id,
            'kelurahan_id' => $request->kelurahan_id,
            'nama_lahan' => $request->nama_lahan,
            'alamat_detail' => $request->alamat_detail,

            // auto system
            'status_verifikasi' => 'PENDING',

            // sementara kosong (diisi petugas nanti)
            'pemilik_lahan' => $request->pemilik_lahan ?? null,
            'tipe_rawa' => $request->tipe_rawa ?? null,
            'tahun_lbs' => $request->tahun_lbs ?? '2024',
            'luas_lahan_hektar' => $request->luas_lahan_hektar ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lahan berhasil dikirim',
            'data' => $data
        ]);
    }
}