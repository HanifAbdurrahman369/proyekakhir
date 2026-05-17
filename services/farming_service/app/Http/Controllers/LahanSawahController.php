<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LahanSawah;
use Illuminate\Support\Facades\DB;

class LahanSawahController extends Controller
{
    /**
     * Menampilkan semua data lahan sawah
     * termasuk polygon GIS dalam format GeoJSON
     */
    public function index()
    {
        $data = LahanSawah::selectRaw("
            id,
            nama_lahan,
            pemilik_lahan,
            luas_lahan_hektar,
            hasil_panen_ton,
            produktivitas_ton_ha,
            ST_AsGeoJSON(polygon_area) as polygon_area
        ")->get();

        return response()->json([
            'success' => true,
            'message' => 'Data lahan sawah berhasil diambil',
            'data' => $data
        ], 200);
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
}