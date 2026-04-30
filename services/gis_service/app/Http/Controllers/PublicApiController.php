<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicApiController extends Controller
{
    // =====================================================================
    // 1. API UNTUK DATA STATISTIK
    // =====================================================================
    public function getStatistik()
    {
        // Menghitung jumlah total kecamatan dan kelurahan
        $totalKecamatan = DB::table('kecamatan')->count();
        $totalKelurahan = DB::table('kelurahan')->count();
        
        // 1. Total Lahan Sawah = Menghitung jumlah baris di tabel lahan_sawah
        $totalLahanSawah = DB::table('lahan_sawah')->count();
        
        // 2. Total Luas Lahan = Menjumlahkan nilai kolom luas_lahan_hektar dari semua baris
        $totalLuasHektar = DB::table('lahan_sawah')->sum('luas_lahan_hektar');

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_kecamatan' => $totalKecamatan,
                'total_kelurahan' => $totalKelurahan,
                'total_lahan_sawah' => $totalLahanSawah,
                // Mengubah format angka menjadi 2 desimal (contoh: 17.50)
                'total_luas_ha' => number_format($totalLuasHektar ?? 0, 2, '.', '') 
            ],
            'message' => 'Data statistik berhasil diambil'
        ]);
    }

    // =====================================================================
    // 2. API UNTUK DATA MAP SPASIAL (POLYGON LAHAN SAWAH)
    // =====================================================================
    // =====================================================================
    // 2. API UNTUK DATA MAP SPASIAL (POLYGON LAHAN SAWAH)
    // =====================================================================
    public function getMapData()
    {
        // MELAKUKAN JOIN KE TABEL KECAMATAN DAN KELURAHAN
        $lahanSawah = DB::table('lahan_sawah')
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
            ->select(
                'lahan_sawah.id',
                'lahan_sawah.nama_lahan',
                'lahan_sawah.pemilik_lahan',
                'lahan_sawah.tipe_rawa',
                'lahan_sawah.luas_lahan_hektar',
                'lahan_sawah.produktivitas_ton_ha',
                'lahan_sawah.alamat_detail', // Menarik alamat detail
                'kecamatan.nama_kecamatan',  // Menarik nama kecamatan
                'kelurahan.nama_kelurahan',  // Menarik nama kelurahan
                DB::raw('ST_AsGeoJSON(lahan_sawah.polygon_area) as geojson')
            )
            ->get();

        $features = [];
        foreach ($lahanSawah as $lahan) {
            // Kalkulasi spesifik per blok lahan (Luas x Produktivitas)
            $estimasiHasil = $lahan->luas_lahan_hektar * $lahan->produktivitas_ton_ha;

            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($lahan->geojson),
                'properties' => [
                    'nama_lahan' => $lahan->nama_lahan,
                    'pemilik' => $lahan->pemilik_lahan,
                    'luas_ha' => $lahan->luas_lahan_hektar,
                    'produktivitas' => $lahan->produktivitas_ton_ha,
                    'total_panen' => number_format($estimasiHasil, 2, '.', ''),
                    // Menambahkan properti baru untuk ditampilkan di Frontend
                    'alamat_detail' => $lahan->alamat_detail ?? 'Belum ada data alamat',
                    'kecamatan' => $lahan->nama_kecamatan ?? 'Tidak diketahui',
                    'kelurahan' => $lahan->nama_kelurahan ?? 'Tidak diketahui'
                ]
            ];
        }
        return response()->json(['type' => 'FeatureCollection', 'features' => $features]);
    }

    // =====================================================================
    // 3. API UNTUK BATAS WILAYAH KABUPATEN BARITO KUALA
    // =====================================================================
    public function getBatasWilayah()
    {
        $kabupaten = DB::table('kabupaten')
            ->where('nama_kabupaten', 'LIKE', '%Barito Kuala%')
            ->select('polygon_baritokuala')
            ->first();

        if ($kabupaten && $kabupaten->polygon_baritokuala) {
            // Ambil teks mentah dari database
            $rawJson = trim($kabupaten->polygon_baritokuala);
            
            // Bersihkan karakter BOM tersembunyi yang sering merusak JSON
            $rawJson = preg_replace('/^[\xef\xbb\xbf]+/', '', $rawJson);
            
            // Kembalikan langsung sebagai respons JSON murni tanpa proses decode yang berisiko
            return response($rawJson, 200)->header('Content-Type', 'application/json');
        }

        return response()->json(['error' => 'Data polygon_baritokuala kosong atau tidak ditemukan'], 404);
    }
}