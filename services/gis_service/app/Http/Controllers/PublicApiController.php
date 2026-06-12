<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class PublicApiController extends Controller
{
    public function getStatistik()
    {
        $totalKecamatan = DB::table('kecamatan')->count();
        $totalKelurahan = DB::table('kelurahan')->count();
        $totalLahanSawah = $this->lahanPublikQuery()->count();
        $totalLuasHektar = $this->lahanPublikQuery()->sum('luas_lahan_hektar');
        $totalPanen = $this->lahanPublikQuery()->sum('hasil_panen_ton');

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'summary' => [
                    'total_kecamatan' => $totalKecamatan,
                    'total_kelurahan' => $totalKelurahan,
                    'total_lahan_sawah' => $totalLahanSawah,
                    'total_luas_ha' => round((float) $totalLuasHektar, 2),
                    'total_panen_ton' => round((float) $totalPanen, 2),
                ],
                'chart_panen_kecamatan' => $this->lahanPublikQuery()
                    ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
                    ->select('kecamatan.nama_kecamatan', DB::raw('COALESCE(SUM(lahan_sawah.hasil_panen_ton),0) as total_panen'))
                    ->groupBy('kecamatan.nama_kecamatan')
                    ->orderBy('kecamatan.nama_kecamatan')
                    ->get(),

                'chart_luas_tipe_rawa' => $this->lahanPublikQuery()
                    ->select('tipe_rawa', DB::raw('COALESCE(SUM(luas_lahan_hektar),0) as total_luas'))
                    ->groupBy('tipe_rawa')
                    ->orderBy('tipe_rawa')
                    ->get(),

                'chart_produktivitas_lahan' => $this->lahanPublikQuery()
                    ->select('nama_lahan', 'produktivitas_ton_ha')
                    ->orderBy('nama_lahan')
                    ->get(),

                'chart_luas_kecamatan' => $this->lahanPublikQuery()
                    ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
                    ->select('kecamatan.nama_kecamatan', DB::raw('COALESCE(SUM(lahan_sawah.luas_lahan_hektar),0) as total_luas'))
                    ->groupBy('kecamatan.nama_kecamatan')
                    ->orderBy('kecamatan.nama_kecamatan')
                    ->get(),

                'tabel_rekap' => $this->lahanPublikQuery()
                    ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
                    ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
                    ->select(
                        'kecamatan.nama_kecamatan',
                        'kelurahan.nama_kelurahan',
                        'lahan_sawah.tahun_lbs',
                        DB::raw('COUNT(lahan_sawah.id) as jumlah_lahan'),
                        DB::raw('COALESCE(SUM(lahan_sawah.luas_lahan_hektar),0) as total_luas'),
                        DB::raw('COALESCE(SUM(lahan_sawah.hasil_panen_ton),0) as total_panen')
                    )
                    ->groupBy('kecamatan.nama_kecamatan', 'kelurahan.nama_kelurahan', 'lahan_sawah.tahun_lbs')
                    ->orderBy('kecamatan.nama_kecamatan')
                    ->get(),
            ],
            'message' => 'Data statistik berhasil diambil'
        ]);
    }

    public function getMapData()
    {
        $rows = $this->lahanPublikQuery()
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
            ->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id')
            ->select(
                'lahan_sawah.id',
                'lahan_sawah.user_id',
                'lahan_sawah.kecamatan_id',
                'lahan_sawah.kelurahan_id',
                'lahan_sawah.tipe_lahan_id',
                'lahan_sawah.nama_lahan',
                'lahan_sawah.pemilik_lahan',
                'lahan_sawah.tipe_rawa',
                'lahan_sawah.tahun_lbs',
                'lahan_sawah.luas_lahan_hektar',
                'lahan_sawah.hasil_panen_ton',
                'lahan_sawah.produktivitas_ton_ha',
                'lahan_sawah.alamat_detail',
                'lahan_sawah.latitude',
                'lahan_sawah.longitude',
                'kecamatan.nama_kecamatan',
                'kelurahan.nama_kelurahan',
                'tipe_lahan.nama_tipe',
                DB::raw('ST_AsGeoJSON(lahan_sawah.polygon_area) as geojson')
            )
            ->get();

        $features = [];

        foreach ($rows as $row) {
            $geometry = $row->geojson ? json_decode($row->geojson, true) : null;

            if (!$geometry && $row->latitude && $row->longitude) {
                $geometry = [
                    'type' => 'Point',
                    'coordinates' => [(float) $row->longitude, (float) $row->latitude],
                ];
            }

            if (!$geometry) {
                continue;
            }

            $features[] = [
                'type' => 'Feature',
                'geometry' => $geometry,
                'properties' => [
                    'id' => $row->id,
                    'user_id' => $row->user_id,
                    'kecamatan_id' => $row->kecamatan_id,
                    'kelurahan_id' => $row->kelurahan_id,
                    'tipe_lahan_id' => $row->tipe_lahan_id,

                    'nama_lahan' => $row->nama_lahan,
                    'pemilik_lahan' => $row->pemilik_lahan,
                    'pemilik' => $row->pemilik_lahan,

                    'tipe_rawa' => $row->tipe_rawa,
                    'nama_tipe' => $row->nama_tipe,
                    'tahun_lbs' => $row->tahun_lbs,

                    'luas_lahan_hektar' => (float) $row->luas_lahan_hektar,
                    'luas_ha' => (float) $row->luas_lahan_hektar,

                    'hasil_panen_ton' => (float) $row->hasil_panen_ton,
                    'hasil_panen' => (float) $row->hasil_panen_ton,

                    'produktivitas_ton_ha' => (float) $row->produktivitas_ton_ha,
                    'produktivitas' => (float) $row->produktivitas_ton_ha,

                    'alamat_detail' => $row->alamat_detail,
                    'latitude' => $row->latitude ? (float) $row->latitude : null,
                    'longitude' => $row->longitude ? (float) $row->longitude : null,

                    'nama_kecamatan' => $row->nama_kecamatan,
                    'nama_kelurahan' => $row->nama_kelurahan,
                    'kecamatan' => $row->nama_kecamatan,
                    'kelurahan' => $row->nama_kelurahan,
                ]
            ];
        }

        return response()->json([
            'success' => true,
            'type' => 'FeatureCollection',
            'features' => $features,
            'data' => [
                'type' => 'FeatureCollection',
                'features' => $features,
            ],
        ]);
    }

    private function lahanPublikQuery()
    {
        return DB::table('lahan_sawah')
            ->where('lahan_sawah.status_verifikasi', 'DITERIMA')
            ->whereNotNull('lahan_sawah.latitude')
            ->whereNotNull('lahan_sawah.longitude')
            ->whereNotNull('lahan_sawah.polygon_area');
    }

    public function getBatasWilayah()
    {
        $kabupaten = DB::table('kabupaten')
            ->where('nama_kabupaten', 'LIKE', '%Barito Kuala%')
            ->select('polygon_baritokuala')
            ->first();

        if (!$kabupaten || !$kabupaten->polygon_baritokuala) {
            return response()->json([
                'success' => false,
                'message' => 'Data polygon Barito Kuala tidak ditemukan'
            ], 404);
        }

        $rawJson = trim($kabupaten->polygon_baritokuala);
        $rawJson = preg_replace('/^[\xEF\xBB\xBF]+/', '', $rawJson);

        return response($rawJson, 200)->header('Content-Type', 'application/json');
    }
}
