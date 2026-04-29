<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicApiController extends Controller
{
    // 1. API UNTUK DATA STATISTIK
    public function getStatistik()
    {
        // Menghitung jumlah wilayah
        $totalKecamatan = DB::table('kecamatan')->count();
        $totalKelurahan = DB::table('kelurahan')->count();
        
        // Menjumlahkan total produksi panen (Ton)
        $totalPanen = DB::table('hasil_panen')->sum('total_produksi_ton');

        // Mengirim respon dalam bentuk JSON
        return response()->json([
            'status' => 'success',
            'data' => [
                'total_kecamatan' => $totalKecamatan,
                'total_kelurahan' => $totalKelurahan,
                'total_panen_ton' => $totalPanen
            ]
        ]);
    }

    // 2. API UNTUK DATA MAP SPASIAL (POLYGON)
    public function getMapData()
    {
        // Mengambil data lahan dan mengubah format Polygon MySQL menjadi GeoJSON
        $lahanSawah = DB::table('lahan_sawah')
            ->select(
                'id',
                'nama_lahan',
                'pemilik_lahan',
                'tipe_rawa',
                'luas_lahan_hektar',
                DB::raw('ST_AsGeoJSON(polygon_area) as geojson')
            )
            ->get();

        // Menyusun ulang data menjadi format 'FeatureCollection' standar peta Leaflet
        $features = [];
        foreach ($lahanSawah as $lahan) {
            $features[] = [
                'type' => 'Feature',
                'geometry' => json_decode($lahan->geojson),
                'properties' => [
                    'id' => $lahan->id,
                    'nama_lahan' => $lahan->nama_lahan,
                    'pemilik' => $lahan->pemilik_lahan,
                    'tipe_rawa' => $lahan->tipe_rawa,
                    'luas_ha' => $lahan->luas_lahan_hektar
                ]
            ];
        }

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features
        ]);
    }

    // 3. API UNTUK BATAS WILAYAH KABUPATEN
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