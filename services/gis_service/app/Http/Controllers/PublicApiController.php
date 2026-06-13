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
        $totalPanen = $this->totalPanenDiterimaPublik();
        $rekapRows = $this->buildTabelRekap();

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
                'chart_panen_kecamatan' => $this->chartPanenKecamatan(),

                'chart_luas_tipe_lahan' => $this->chartLuasTipeLahan(),

                'chart_produktivitas_lahan' => $this->chartProduktivitasLahan(),

                'chart_luas_kecamatan' => $this->lahanPublikQuery()
                    ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
                    ->select('kecamatan.nama_kecamatan', DB::raw('ROUND(COALESCE(SUM(lahan_sawah.luas_lahan_hektar),0), 2) as total_luas'))
                    ->groupBy('kecamatan.nama_kecamatan')
                    ->orderBy('kecamatan.nama_kecamatan')
                    ->get(),

                'tipe_lahan_options' => $this->tipeLahanOptions($rekapRows),
                'tabel_rekap' => $rekapRows,
            ],
            'message' => 'Data statistik berhasil diambil'
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function getMapData()
    {
        $rows = $this->lahanPublikQuery()
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
            ->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id')
            ->leftJoinSub($this->panenDiterimaPerLahanQuery(), 'panen_lahan', function ($join) {
                $join->on('panen_lahan.lahan_id', '=', 'lahan_sawah.id');
            })
            ->select(
                'lahan_sawah.id',
                'lahan_sawah.user_id',
                'lahan_sawah.kecamatan_id',
                'lahan_sawah.kelurahan_id',
                'lahan_sawah.tipe_lahan_id',
                'lahan_sawah.nama_lahan',
                'lahan_sawah.pemilik_lahan',
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
                DB::raw('COALESCE(panen_lahan.total_panen,0) as total_panen_diterima'),
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

                    'tipe_lahan' => $row->nama_tipe ?: 'Belum Ditentukan',
                    'nama_tipe' => $row->nama_tipe,
                    'tahun_lbs' => $row->tahun_lbs,

                    'luas_lahan_hektar' => (float) $row->luas_lahan_hektar,
                    'luas_ha' => (float) $row->luas_lahan_hektar,

                    'hasil_panen_ton' => (float) $row->total_panen_diterima,
                    'hasil_panen' => (float) $row->total_panen_diterima,

                    'produktivitas_ton_ha' => (float) (($row->luas_lahan_hektar ?? 0) > 0 ? round($row->total_panen_diterima / $row->luas_lahan_hektar, 2) : 0),
                    'produktivitas' => (float) (($row->luas_lahan_hektar ?? 0) > 0 ? round($row->total_panen_diterima / $row->luas_lahan_hektar, 2) : 0),

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
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function lahanPublikQuery()
    {
        return DB::table('lahan_sawah')
            ->where('lahan_sawah.status_verifikasi', 'DITERIMA')
            ->whereNotNull('lahan_sawah.latitude')
            ->whereNotNull('lahan_sawah.longitude')
            ->whereNotNull('lahan_sawah.polygon_area');
    }

    private function panenDiterimaPerLahanQuery()
    {
        return DB::table('siklus_tanam')
            ->select('lahan_id', DB::raw('COALESCE(SUM(hasil_panen),0) as total_panen'))
            ->where('status_verifikasi', 'DITERIMA')
            ->whereNotNull('hasil_panen')
            ->groupBy('lahan_id');
    }

    private function totalPanenDiterimaPublik(): float
    {
        return (float) $this->lahanPublikQuery()
            ->leftJoinSub($this->panenDiterimaPerLahanQuery(), 'panen_lahan', function ($join) {
                $join->on('panen_lahan.lahan_id', '=', 'lahan_sawah.id');
            })
            ->sum(DB::raw('COALESCE(panen_lahan.total_panen,0)'));
    }

    private function chartPanenKecamatan()
    {
        return $this->lahanPublikQuery()
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoinSub($this->panenDiterimaPerLahanQuery(), 'panen_lahan', function ($join) {
                $join->on('panen_lahan.lahan_id', '=', 'lahan_sawah.id');
            })
            ->select('kecamatan.nama_kecamatan', DB::raw('ROUND(COALESCE(SUM(panen_lahan.total_panen),0), 2) as total_panen'))
            ->groupBy('kecamatan.nama_kecamatan')
            ->orderBy('kecamatan.nama_kecamatan')
            ->get();
    }

    private function chartLuasTipeLahan()
    {
        return $this->lahanPublikQuery()
            ->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id')
            ->select(
                'lahan_sawah.tipe_lahan_id',
                DB::raw("COALESCE(tipe_lahan.nama_tipe, 'Belum Ditentukan') as nama_tipe"),
                DB::raw("COALESCE(tipe_lahan.nama_tipe, 'Belum Ditentukan') as tipe_lahan"),
                DB::raw('ROUND(COALESCE(SUM(lahan_sawah.luas_lahan_hektar),0), 2) as total_luas')
            )
            ->groupBy('lahan_sawah.tipe_lahan_id', 'tipe_lahan.nama_tipe')
            ->orderBy('tipe_lahan.nama_tipe')
            ->get();
    }

    private function chartProduktivitasLahan()
    {
        return $this->lahanPublikQuery()
            ->leftJoinSub($this->panenDiterimaPerLahanQuery(), 'panen_lahan', function ($join) {
                $join->on('panen_lahan.lahan_id', '=', 'lahan_sawah.id');
            })
            ->select(
                'lahan_sawah.nama_lahan',
                DB::raw('COALESCE(panen_lahan.total_panen,0) as total_panen'),
                DB::raw('CASE WHEN lahan_sawah.luas_lahan_hektar > 0 THEN ROUND(COALESCE(panen_lahan.total_panen,0) / lahan_sawah.luas_lahan_hektar, 2) ELSE 0 END as produktivitas_ton_ha')
            )
            ->orderBy('lahan_sawah.nama_lahan')
            ->get();
    }

    private function buildTabelRekap()
    {
        $rows = $this->lahanPublikQuery()
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
            ->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id')
            ->leftJoinSub($this->panenDiterimaPerLahanQuery(), 'panen_lahan', function ($join) {
                $join->on('panen_lahan.lahan_id', '=', 'lahan_sawah.id');
            })
            ->select(
                'lahan_sawah.id',
                'lahan_sawah.tahun_lbs',
                'lahan_sawah.tipe_lahan_id',
                'lahan_sawah.luas_lahan_hektar',
                'kecamatan.nama_kecamatan',
                'kelurahan.nama_kelurahan',
                DB::raw("COALESCE(tipe_lahan.nama_tipe, 'Belum Ditentukan') as nama_tipe"),
                DB::raw('COALESCE(panen_lahan.total_panen,0) as total_panen_lahan')
            )
            ->orderBy('kecamatan.nama_kecamatan')
            ->orderBy('kelurahan.nama_kelurahan')
            ->get();

        return $rows
            ->groupBy(fn ($row) => implode('|', [
                $row->nama_kecamatan ?: '-',
                $row->nama_kelurahan ?: '-',
                $row->tahun_lbs ?: '-',
            ]))
            ->map(function ($items) {
                $first = $items->first();
                $rincianTipe = $items
                    ->groupBy(fn ($row) => ($row->tipe_lahan_id ?: 'unknown') . '|' . ($row->nama_tipe ?: 'Belum Ditentukan'))
                    ->map(function ($tipeItems) {
                        $firstTipe = $tipeItems->first();

                        return [
                            'tipe_lahan_id' => $firstTipe->tipe_lahan_id,
                            'nama_tipe' => $firstTipe->nama_tipe ?: 'Belum Ditentukan',
                            'total_luas' => round((float) $tipeItems->sum('luas_lahan_hektar'), 2),
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'nama_kecamatan' => $first->nama_kecamatan ?: '-',
                    'nama_kelurahan' => $first->nama_kelurahan ?: '-',
                    'tahun_lbs' => $first->tahun_lbs,
                    'jumlah_lahan' => $items->pluck('id')->unique()->count(),
                    'total_luas' => round((float) $items->sum('luas_lahan_hektar'), 2),
                    'total_panen' => round((float) $items->sum('total_panen_lahan'), 2),
                    'rincian_tipe_lahan' => $rincianTipe,
                    'tipe_lahan_ids' => collect($rincianTipe)->pluck('tipe_lahan_id')->filter()->values()->all(),
                ];
            })
            ->values();
    }

    private function tipeLahanOptions($rekapRows)
    {
        return collect($rekapRows)
            ->flatMap(fn ($row) => $row['rincian_tipe_lahan'] ?? [])
            ->filter(fn ($row) => !empty($row['tipe_lahan_id']))
            ->unique('tipe_lahan_id')
            ->sortBy('nama_tipe')
            ->values()
            ->map(fn ($row) => [
                'id' => $row['tipe_lahan_id'],
                'nama_tipe' => $row['nama_tipe'],
            ])
            ->all();
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

    public function getBatasKecamatan()
    {
        $rows = DB::table('kecamatan')
            ->whereNotNull('polygon_geojson')
            ->where('polygon_geojson', '!=', '')
            ->select('id', 'nama_kecamatan', 'polygon_geojson')
            ->orderBy('nama_kecamatan')
            ->get();

        $features = [];

        foreach ($rows as $row) {
            $geojson = json_decode($row->polygon_geojson, true);

            if (!is_array($geojson)) {
                continue;
            }

            $features = array_merge($features, $this->normalisasiFeatureKecamatan($geojson, [
                'id' => $row->id,
                'kecamatan_id' => $row->id,
                'nama_kecamatan' => $row->nama_kecamatan,
            ]));
        }

        return response()->json([
            'success' => true,
            'type' => 'FeatureCollection',
            'features' => $features,
            'data' => [
                'type' => 'FeatureCollection',
                'features' => $features,
            ],
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function normalisasiFeatureKecamatan(array $geojson, array $baseProperties): array
    {
        if (($geojson['type'] ?? null) === 'FeatureCollection') {
            return collect($geojson['features'] ?? [])
                ->filter(fn ($feature) => is_array($feature) && !empty($feature['geometry']))
                ->map(function ($feature) use ($baseProperties) {
                    $feature['properties'] = array_merge(
                        (array) ($feature['properties'] ?? []),
                        $baseProperties
                    );

                    return [
                        'type' => 'Feature',
                        'geometry' => $feature['geometry'],
                        'properties' => $feature['properties'],
                    ];
                })
                ->values()
                ->all();
        }

        if (($geojson['type'] ?? null) === 'Feature' && !empty($geojson['geometry'])) {
            return [[
                'type' => 'Feature',
                'geometry' => $geojson['geometry'],
                'properties' => array_merge((array) ($geojson['properties'] ?? []), $baseProperties),
            ]];
        }

        if (!empty($geojson['type']) && !empty($geojson['coordinates'])) {
            return [[
                'type' => 'Feature',
                'geometry' => $geojson,
                'properties' => $baseProperties,
            ]];
        }

        return [];
    }
}
