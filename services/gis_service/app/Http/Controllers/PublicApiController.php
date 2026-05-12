<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicApiController extends Controller
{
    // =====================================================================
    // 1. API UNTUK DATA STATISTIK & GRAFIK
    // =====================================================================
    public function getStatistik()
    {
        // 1. Angka Ringkasan (KPI)
        $totalKecamatan = DB::table('kecamatan')->count();
        $totalKelurahan = DB::table('kelurahan')->count();
        $totalLahanSawah = DB::table('lahan_sawah')->count();
        $totalLuasHektar = DB::table('lahan_sawah')->sum('luas_lahan_hektar');

        // 2. Data Grafik Batang (Bar): Hasil Panen per Kecamatan
        $panenPerKecamatan = DB::table('lahan_sawah')
            ->join('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->select('kecamatan.nama_kecamatan', DB::raw('SUM(lahan_sawah.hasil_panen_ton) as total_panen'))
            ->groupBy('kecamatan.nama_kecamatan')
            ->get();

        // 3. Data Grafik Bulat (Doughnut): Luas Lahan per Tipe Rawa
        $luasPerTipeRawa = DB::table('lahan_sawah')
            ->select('tipe_rawa', DB::raw('SUM(luas_lahan_hektar) as total_luas'))
            ->groupBy('tipe_rawa')
            ->get();

        // 4. Data Grafik Garis (Line): Tren Produktivitas per Lahan
        $produktivitasLahan = DB::table('lahan_sawah')
            ->select('nama_lahan', 'produktivitas_ton_ha')
            ->orderBy('nama_lahan')
            ->get();

        // 5. Data Grafik Area (Polar Area): Luas Lahan per Kecamatan
        $luasPerKecamatan = DB::table('lahan_sawah')
            ->join('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->select('kecamatan.nama_kecamatan', DB::raw('SUM(lahan_sawah.luas_lahan_hektar) as total_luas'))
            ->groupBy('kecamatan.nama_kecamatan')
            ->get();

        // 6. Data Tabel Rekapitulasi (Kecamatan, Kelurahan, Jumlah Lahan, tabelTotal Panen)
            $tabelRekap = DB::table('lahan_sawah')
            ->join('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
            ->select(
                'kecamatan.nama_kecamatan',
                'kelurahan.nama_kelurahan',
                'lahan_sawah.tahun_lbs', // Ambil data tahun LBS
                DB::raw('COUNT(lahan_sawah.id) as jumlah_lahan'),
                DB::raw('SUM(lahan_sawah.luas_lahan_hektar) as total_luas'),
                DB::raw('SUM(lahan_sawah.hasil_panen_ton) as total_panen'),
                DB::raw('SUM(CASE WHEN tipe_lahan_id = 1 THEN luas_lahan_hektar ELSE 0 END) as luas_a'),
                DB::raw('SUM(CASE WHEN tipe_lahan_id = 2 THEN luas_lahan_hektar ELSE 0 END) as luas_b'),
                DB::raw('SUM(CASE WHEN tipe_lahan_id = 3 THEN luas_lahan_hektar ELSE 0 END) as luas_c'),
                DB::raw('SUM(CASE WHEN tipe_lahan_id = 4 THEN luas_lahan_hektar ELSE 0 END) as luas_d')
            )
            ->groupBy('kecamatan.nama_kecamatan', 'kelurahan.nama_kelurahan', 'lahan_sawah.tahun_lbs')
            ->orderBy('kecamatan.nama_kecamatan')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'total_kecamatan' => $totalKecamatan,
                    'total_kelurahan' => $totalKelurahan,
                    'total_lahan_sawah' => $totalLahanSawah,
                    'total_luas_ha' => number_format($totalLuasHektar ?? 0, 2, '.', '') 
                ],
                'chart_panen_kecamatan' => $panenPerKecamatan,
                'chart_luas_tipe_rawa' => $luasPerTipeRawa,
                'chart_produktivitas_lahan' => $produktivitasLahan,
                'chart_luas_kecamatan' => $luasPerKecamatan,
                'tabel_rekap' => $tabelRekap // Tambahan data tabel
            ],
            'message' => 'Data statistik dan grafik berhasil diambil'
        ]);
    }

    // =====================================================================
    // 2. API UNTUK DATA MAP SPASIAL (POLYGON LAHAN SAWAH)
    // =====================================================================
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
                'lahan_sawah.hasil_panen_ton', // Menarik hasil panen langsung dari DB
                'lahan_sawah.produktivitas_ton_ha', // Menarik produktivitas
                'lahan_sawah.alamat_detail', 
                'kecamatan.nama_kecamatan',  
                'kelurahan.nama_kelurahan',  
                DB::raw('ST_AsGeoJSON(lahan_sawah.polygon_area) as geojson')
            )
            ->get();

        $features = [];
        foreach ($lahanSawah as $lahan) {
            // Kalkulasi manual telah dihapus karena data diambil dari kolom hasil_panen_ton

            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($lahan->geojson),
                'properties' => [
                    'nama_lahan' => $lahan->nama_lahan,
                    'pemilik' => $lahan->pemilik_lahan,
                    'luas_ha' => $lahan->luas_lahan_hektar,
                    'hasil_panen' => $lahan->hasil_panen_ton, // Menggunakan kolom hasil_panen_ton
                    'produktivitas' => $lahan->produktivitas_ton_ha, // Menggunakan kolom produktivitas
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