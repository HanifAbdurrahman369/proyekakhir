<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

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
            ->leftJoin('users as pemilik', 'lahan_sawah.pemilik_id', '=', 'pemilik.id')
            ->leftJoinSub($this->panenDiterimaPerLahanQuery(), 'panen_lahan', function ($join) {
                $join->on('panen_lahan.lahan_id', '=', 'lahan_sawah.id');
            })
            ->select(
                'lahan_sawah.id',
                'lahan_sawah.pemilik_id',
                'lahan_sawah.petani_id',
                'lahan_sawah.kecamatan_id',
                'lahan_sawah.kelurahan_id',
                'lahan_sawah.tipe_lahan_id',
                'lahan_sawah.nama_lahan',
                'pemilik.nama_lengkap as pemilik_lahan',
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

        foreach ($rows as $index => $row) {
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
                    'nomor_urut' => $index + 1,
                    'id' => $row->id,
                    'user_id' => $row->pemilik_id,
                    'pemilik_id' => $row->pemilik_id,
                    'petani_id' => $row->petani_id,
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

                    'hasil_panen_ton' => (float) ($row->hasil_panen_ton ?? 0),
                    'hasil_panen' => (float) ($row->hasil_panen_ton ?? 0),

                    'produktivitas_ton_ha' => (float) ($row->produktivitas_ton_ha ?? 0),
                    'produktivitas' => (float) ($row->produktivitas_ton_ha ?? 0),
                    'total_panen_historis_ton' => (float) $row->total_panen_diterima,

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
            ->where('lahan_sawah.status_verifikasi', 'DITERIMA');
    }

    private function panenDiterimaPerLahanQuery()
    {
        if (Schema::hasTable('panen_padi')) {
            return DB::table('panen_padi')
                ->select('lahan_id', DB::raw('COALESCE(SUM(hasil_panen_ton),0) as total_panen'))
                ->where('status_verifikasi', 'DITERIMA')
                ->whereDate('tanggal_panen', '<=', now()->toDateString())
                ->groupBy('lahan_id');
        }

        return DB::table('lahan_sawah')
            ->select('id as lahan_id', DB::raw('0 as total_panen'))
            ->whereRaw('1 = 0');
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
        if (Schema::hasTable('panen_padi')) {
            return DB::table('panen_padi as rp')
                ->join('lahan_sawah as ls', 'ls.id', '=', 'rp.lahan_id')
                ->leftJoin('kecamatan', 'ls.kecamatan_id', '=', 'kecamatan.id')
                ->where('rp.status_verifikasi', 'DITERIMA')
                ->whereDate('rp.tanggal_panen', '<=', now()->toDateString())
                ->where('ls.status_verifikasi', 'DITERIMA')
                ->select(
                    'kecamatan.nama_kecamatan as nama_lahan',
                    DB::raw('ROUND(SUM(rp.hasil_panen_ton), 2) as total_panen'),
                    DB::raw('ROUND(SUM(rp.luas_lahan_ha), 2) as total_luas_panen'),
                    DB::raw('CASE WHEN SUM(rp.luas_lahan_ha) > 0 THEN ROUND(SUM(rp.hasil_panen_ton) / SUM(rp.luas_lahan_ha), 2) ELSE 0 END as produktivitas_ton_ha')
                )
                ->groupBy('kecamatan.nama_kecamatan')
                ->orderBy('kecamatan.nama_kecamatan')
                ->get()
                ->map(function ($row) {
                    $row->periode_label = $row->nama_lahan ?: 'Belum Ditentukan';
                    $row->nama_lahan = $row->periode_label;
                    return $row;
                });
        }

        return $this->lahanPublikQuery()
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoinSub($this->panenDiterimaPerLahanQuery(), 'panen_lahan', function ($join) {
                $join->on('panen_lahan.lahan_id', '=', 'lahan_sawah.id');
            })
            ->select(
                'kecamatan.nama_kecamatan as nama_lahan',
                DB::raw('SUM(COALESCE(panen_lahan.total_panen,0)) as total_panen'),
                DB::raw('CASE WHEN SUM(lahan_sawah.luas_lahan_hektar) > 0 THEN ROUND(SUM(COALESCE(panen_lahan.total_panen,0)) / SUM(lahan_sawah.luas_lahan_hektar), 2) ELSE 0 END as produktivitas_ton_ha')
            )
            ->groupBy('kecamatan.nama_kecamatan')
            ->orderBy('kecamatan.nama_kecamatan')
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
        $rawJson = str_ireplace('Barito Delta', 'Barito Kuala', $rawJson);

        $geojson = json_decode($rawJson, true);

        if (is_array($geojson)) {
            $geojson['name'] = 'barito_kuala_boundary_full';
            $geojson['meta'] = [
                'nama_wilayah' => 'Barito Kuala',
                'warna_garis' => '#203c10',
                'warna_isi' => 'transparent',
                'fill_opacity' => 0,
                'style_contract' => 'properties.warna_peta/fill_color dapat dipakai frontend web dan mobile',
            ];

            if (($geojson['type'] ?? null) === 'FeatureCollection') {
                $geojson['features'] = collect($geojson['features'] ?? [])
                    ->filter(fn ($feature) => is_array($feature) && !empty($feature['geometry']))
                    ->map(function ($feature) {
                        $feature['properties'] = array_merge((array) ($feature['properties'] ?? []), [
                            'nama' => 'Kabupaten Barito Kuala',
                            'nama_kabupaten' => 'Barito Kuala',
                            'label' => 'Barito Kuala',
                            'warna_peta' => '#203c10',
                            'fill_color' => 'transparent',
                            'fill_opacity' => 0,
                        ]);

                        return $feature;
                    })
                    ->values()
                    ->all();
            }

            $rawJson = json_encode($geojson, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return response($rawJson, 200)
            ->header('Content-Type', 'application/json')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function getBatasKecamatan()
    {
        $rows = DB::table('kecamatan')
            ->whereNotNull('polygon_geojson')
            ->where('polygon_geojson', '!=', '')
            ->select('id', 'nama_kecamatan', 'polygon_geojson')
            ->orderBy('id')
            ->get();

        $agregatProduktivitas = $this->agregatProduktivitasKecamatan();
        $ambangProduktivitas = $this->ambangProduktivitasKecamatan($agregatProduktivitas);
        $distribusiProduktivitas = [
            'tinggi' => 0,
            'sedang' => 0,
            'rendah' => 0,
            'belum-data' => 0,
        ];
        $features = [];

        foreach ($rows as $row) {
            $geojson = json_decode($row->polygon_geojson, true);

            if (!is_array($geojson)) {
                continue;
            }

            $statistik = $agregatProduktivitas[$row->id] ?? [
                'jumlah_lahan' => 0,
                'total_luas_ha' => 0,
                'total_luas_panen_ha' => 0,
                'total_panen_ton' => 0,
                'produktivitas_ton_ha' => 0,
                'sumber_produktivitas' => 'Belum ada data',
            ];
            $kategori = $this->kategoriProduktivitasKecamatan(
                (float) ($statistik['produktivitas_ton_ha'] ?? 0),
                $ambangProduktivitas
            );
            $distribusiProduktivitas[$kategori['key']]++;

            $features = array_merge($features, $this->normalisasiFeatureKecamatan($geojson, [
                'id' => $row->id,
                'kecamatan_id' => $row->id,
                'nama_kecamatan' => $row->nama_kecamatan,
                'label' => $row->nama_kecamatan,
                'jumlah_lahan' => (int) $statistik['jumlah_lahan'],
                'total_luas_ha' => round((float) $statistik['total_luas_ha'], 2),
                'total_luas_panen_ha' => round((float) $statistik['total_luas_panen_ha'], 2),
                'total_panen_ton' => round((float) $statistik['total_panen_ton'], 2),
                'produktivitas_ton_ha' => round((float) $statistik['produktivitas_ton_ha'], 2),
                'sumber_produktivitas' => $statistik['sumber_produktivitas'],
                'kategori_produktivitas' => $kategori['key'],
                'kategori_produktivitas_label' => $kategori['label'],
                'priority_class' => $kategori['key'],
                'warna_peta' => $kategori['stroke'],
                'fill_color' => $kategori['fill'],
                'fill_opacity' => $kategori['key'] === 'belum-data' ? 0.12 : 0.28,
            ]));
        }

        $meta = [
            'jumlah_kecamatan' => $rows->count(),
            'jumlah_feature' => count($features),
            'warna_diambil_dari' => 'properties.kategori_produktivitas/properties.fill_color',
            'label_diambil_dari' => 'properties.nama_kecamatan',
            'klasifikasi' => 'Tertile produktivitas_ton_ha per kecamatan dari data existing',
            'ambang_produktivitas' => $ambangProduktivitas,
            'distribusi_produktivitas' => $distribusiProduktivitas,
        ];

        return response()->json([
            'success' => true,
            'type' => 'FeatureCollection',
            'features' => $features,
            'meta' => $meta,
            'data' => [
                'type' => 'FeatureCollection',
                'features' => $features,
                'meta' => $meta,
            ],
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function agregatProduktivitasKecamatan(): array
    {
        $lahanRows = DB::table('lahan_sawah')
            ->select(
                'lahan_sawah.kecamatan_id',
                DB::raw('COUNT(DISTINCT lahan_sawah.id) as jumlah_lahan'),
                DB::raw('ROUND(COALESCE(SUM(lahan_sawah.luas_lahan_hektar),0), 2) as total_luas_ha'),
                DB::raw('ROUND(COALESCE(SUM(lahan_sawah.hasil_panen_ton),0), 2) as total_panen_lahan'),
                DB::raw('CASE WHEN SUM(COALESCE(lahan_sawah.hasil_panen_ton,0)) > 0 AND SUM(lahan_sawah.luas_lahan_hektar) > 0 THEN ROUND(SUM(COALESCE(lahan_sawah.hasil_panen_ton,0)) / SUM(lahan_sawah.luas_lahan_hektar), 2) WHEN AVG(NULLIF(lahan_sawah.produktivitas_ton_ha,0)) IS NOT NULL THEN ROUND(AVG(NULLIF(lahan_sawah.produktivitas_ton_ha,0)), 2) ELSE 0 END as produktivitas_lahan')
            )
            ->where('lahan_sawah.status_verifikasi', 'DITERIMA')
            ->whereNotNull('lahan_sawah.kecamatan_id')
            ->groupBy('lahan_sawah.kecamatan_id')
            ->get()
            ->keyBy('kecamatan_id');

        $panenRows = collect();

        if (Schema::hasTable('panen_padi')) {
            $panenRows = DB::table('panen_padi as rp')
                ->join('lahan_sawah as ls', 'ls.id', '=', 'rp.lahan_id')
                ->where('rp.status_verifikasi', 'DITERIMA')
                ->whereDate('rp.tanggal_panen', '<=', now()->toDateString())
                ->where('ls.status_verifikasi', 'DITERIMA')
                ->whereNotNull('ls.kecamatan_id')
                ->select(
                    'ls.kecamatan_id',
                    DB::raw('COUNT(DISTINCT ls.id) as jumlah_lahan_panen'),
                    DB::raw('ROUND(COALESCE(SUM(rp.hasil_panen_ton),0), 2) as total_panen_ton'),
                    DB::raw('ROUND(COALESCE(SUM(rp.luas_lahan_ha),0), 2) as total_luas_panen_ha'),
                    DB::raw('CASE WHEN SUM(COALESCE(rp.hasil_panen_ton,0)) > 0 AND SUM(rp.luas_lahan_ha) > 0 THEN ROUND(SUM(rp.hasil_panen_ton) / SUM(rp.luas_lahan_ha), 2) WHEN AVG(NULLIF(rp.produktivitas_ton_ha,0)) IS NOT NULL THEN ROUND(AVG(NULLIF(rp.produktivitas_ton_ha,0)), 2) ELSE 0 END as produktivitas_ton_ha')
                )
                ->groupBy('ls.kecamatan_id')
                ->get()
                ->keyBy('kecamatan_id');
        }

        return $lahanRows
            ->keys()
            ->merge($panenRows->keys())
            ->filter()
            ->unique()
            ->mapWithKeys(function ($kecamatanId) use ($lahanRows, $panenRows) {
                $lahan = $lahanRows->get($kecamatanId);
                $panen = $panenRows->get($kecamatanId);
                $pakaiPanen = $panen && (float) $panen->total_luas_panen_ha > 0;

                $totalLuasHa = (float) ($lahan->total_luas_ha ?? 0);
                $totalLuasPanenHa = $pakaiPanen ? (float) $panen->total_luas_panen_ha : $totalLuasHa;
                $totalPanenTon = $pakaiPanen ? (float) $panen->total_panen_ton : (float) ($lahan->total_panen_lahan ?? 0);
                $produktivitas = $pakaiPanen
                    ? (float) $panen->produktivitas_ton_ha
                    : (float) ($lahan->produktivitas_lahan ?? 0);

                return [
                    $kecamatanId => [
                        'jumlah_lahan' => (int) ($lahan->jumlah_lahan ?? $panen->jumlah_lahan_panen ?? 0),
                        'total_luas_ha' => $totalLuasHa,
                        'total_luas_panen_ha' => $totalLuasPanenHa,
                        'total_panen_ton' => $totalPanenTon,
                        'produktivitas_ton_ha' => $produktivitas,
                        'sumber_produktivitas' => $pakaiPanen ? 'panen_padi diterima' : 'lahan_sawah',
                    ],
                ];
            })
            ->all();
    }

    private function ambangProduktivitasKecamatan(array $agregatProduktivitas): array
    {
        $values = collect($agregatProduktivitas)
            ->pluck('produktivitas_ton_ha')
            ->map(fn ($value) => (float) $value)
            ->filter(fn ($value) => $value > 0)
            ->sort()
            ->values();

        return [
            'rendah_maks' => $this->percentile($values, 33.33),
            'sedang_maks' => $this->percentile($values, 66.67),
            'jumlah_data' => $values->count(),
        ];
    }

    private function percentile($values, float $percentile): ?float
    {
        $count = $values->count();

        if ($count === 0) {
            return null;
        }

        if ($count === 1) {
            return round((float) $values->first(), 2);
        }

        $position = ($count - 1) * ($percentile / 100);
        $lowerIndex = (int) floor($position);
        $upperIndex = (int) ceil($position);
        $lower = (float) $values->get($lowerIndex);
        $upper = (float) $values->get($upperIndex);

        if ($lowerIndex === $upperIndex) {
            return round($lower, 2);
        }

        return round($lower + (($upper - $lower) * ($position - $lowerIndex)), 2);
    }

    private function kategoriProduktivitasKecamatan(float $produktivitas, array $ambang): array
    {
        $classes = [
            'tinggi' => [
                'label' => 'Produktivitas tinggi',
                'stroke' => '#15803d',
                'fill' => '#22c55e',
            ],
            'sedang' => [
                'label' => 'Produktivitas sedang',
                'stroke' => '#b45309',
                'fill' => '#f59e0b',
            ],
            'rendah' => [
                'label' => 'Produktivitas rendah',
                'stroke' => '#b91c1c',
                'fill' => '#ef4444',
            ],
            'belum-data' => [
                'label' => 'Belum ada data',
                'stroke' => '#64748b',
                'fill' => '#94a3b8',
            ],
        ];

        if ($produktivitas <= 0) {
            return ['key' => 'belum-data'] + $classes['belum-data'];
        }

        $rendahMaks = $ambang['rendah_maks'] ?? null;
        $sedangMaks = $ambang['sedang_maks'] ?? null;

        if ($rendahMaks === null || $sedangMaks === null || abs($rendahMaks - $sedangMaks) < 0.01) {
            return ['key' => 'sedang'] + $classes['sedang'];
        }

        if ($produktivitas <= $rendahMaks) {
            return ['key' => 'rendah'] + $classes['rendah'];
        }

        if ($produktivitas <= $sedangMaks) {
            return ['key' => 'sedang'] + $classes['sedang'];
        }

        return ['key' => 'tinggi'] + $classes['tinggi'];
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
