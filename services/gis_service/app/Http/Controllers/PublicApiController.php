<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        $totalLahanSawah = $this->lahanDiterimaQuery()->count();
        $totalLuasHektar = $this->lahanDiterimaQuery()->sum('luas_lahan_hektar');
        $totalLuasTanamHektar = $this->lahanDiterimaQuery()
            ->sum(DB::raw('COALESCE(luas_tanam_hektar, luas_lahan_hektar)'));
        $totalPanen = $this->totalPanenDiterimaPublik();
        $rekapRows = $this->buildTabelRekap();
        $rekapPadiKecamatan = $this->buildRekapPadiKecamatan();

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'summary' => [
                    'total_kecamatan' => $totalKecamatan,
                    'total_kelurahan' => $totalKelurahan,
                    'total_lahan_sawah' => $totalLahanSawah,
                    'total_luas_ha' => round((float) $totalLuasHektar, 2),
                    'total_luas_tanam_ha' => round((float) $totalLuasTanamHektar, 2),
                    'total_panen_ton' => round((float) $totalPanen, 2),
                ],
                'kecamatan_all' => DB::table('kecamatan')
                    ->select('nama_kecamatan')
                    ->orderBy('nama_kecamatan')
                    ->get(),
                'kelurahan_all' => DB::table('kelurahan')
                    ->leftJoin('kecamatan', 'kelurahan.kecamatan_id', '=', 'kecamatan.id')
                    ->select('kelurahan.nama_kelurahan', 'kecamatan.nama_kecamatan')
                    ->orderBy('kelurahan.nama_kelurahan')
                    ->get(),
                'lahan_all' => $this->lahanDiterimaQuery()
                    ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
                    ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
                    ->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id')
                    ->leftJoin('users as pemilik', 'lahan_sawah.pemilik_id', '=', 'pemilik.id')
                    ->select(
                        'lahan_sawah.id',
                        'lahan_sawah.nama_lahan',
                        'lahan_sawah.luas_lahan_hektar as luas',
                        DB::raw('COALESCE(lahan_sawah.luas_tanam_hektar, lahan_sawah.luas_lahan_hektar) as luas_tanam'),
                        'kecamatan.nama_kecamatan',
                        'kelurahan.nama_kelurahan',
                        'pemilik.nama_lengkap as pemilik_nama',
                        DB::raw("COALESCE(tipe_lahan.nama_tipe, 'Belum Ditentukan') as tipe_lahan")
                    )
                    ->orderBy('kecamatan.nama_kecamatan')
                    ->orderBy('kelurahan.nama_kelurahan')
                    ->orderBy('lahan_sawah.nama_lahan')
                    ->get(),

                'chart_panen_kecamatan' => $this->chartPanenKecamatan(),

                'chart_luas_tipe_lahan' => $this->chartLuasTipeLahan(),

                'chart_produktivitas_lahan' => $this->chartProduktivitasLahan(),

                'chart_luas_kecamatan' => $this->chartLuasKecamatan(),

                'tipe_lahan_options' => $this->tipeLahanOptions($rekapRows),
                'tabel_rekap' => $rekapRows,
                'rekap_padi_kecamatan' => $rekapPadiKecamatan,
                'tahun_padi_options' => $this->tahunStatistikPadiOptions(),
            ],
            'message' => 'Data statistik berhasil diambil'
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function getDetailStatistikKecamatan(Request $request, string $kecamatan)
    {
        $resolved = $this->resolveKecamatan($kecamatan);

        if (!$resolved) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Kecamatan tidak ditemukan',
            ], 404);
        }

        $tahun = $request->query('tahun');
        $rows = $this->statistikPadiRowsForKecamatan($resolved->id, $tahun);
        $allRows = $this->statistikPadiRowsForKecamatan($resolved->id);
        $summary = $this->summaryStatistikPadiRows($rows);
        $summaryAllYears = $this->summaryStatistikPadiRows($allRows);

        return response()->json([
            'success' => true,
            'status' => 'success',
            'data' => [
                'kecamatan' => [
                    'id' => $resolved->id,
                    'nama_kecamatan' => $resolved->nama_kecamatan,
                ],
                'filter' => [
                    'tahun' => $tahun ?: 'all',
                ],
                'tahun_options' => $allRows->pluck('tahun')->values()->all(),
                'summary' => $summary,
                'summary_all_years' => $summaryAllYears,
                'narasi' => $this->narasiStatistikPadi($resolved->nama_kecamatan, $summary),
                'rows' => $rows->values()->all(),
            ],
            'message' => 'Detail statistik padi kecamatan berhasil diambil',
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function downloadStatistikKecamatan(Request $request, string $kecamatan)
    {
        $resolved = $this->resolveKecamatan($kecamatan);

        if (!$resolved) {
            abort(404, 'Kecamatan tidak ditemukan');
        }

        $tahun = $request->query('tahun');
        $rows = $this->statistikPadiRowsForKecamatan($resolved->id, $tahun);
        $summary = $this->summaryStatistikPadiRows($rows);
        $filename = 'statistik-padi-' . $this->filenameSlug($resolved->nama_kecamatan)
            . ($tahun ? '-' . $tahun : '-2010-2025') . '.xls';

        $html = $this->buildStatistikPadiExcelHtml($resolved->nama_kecamatan, $rows, $summary, $tahun);

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
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
                'lahan_sawah.assigned_petugas_id',
                'lahan_sawah.kecamatan_id',
                'lahan_sawah.kelurahan_id',
                'lahan_sawah.tipe_lahan_id',
                'lahan_sawah.nama_lahan',
                'pemilik.nama_lengkap as pemilik_lahan',
                'lahan_sawah.tahun_lbs',
                'lahan_sawah.luas_lahan_hektar',
                DB::raw('COALESCE(lahan_sawah.luas_tanam_hektar, lahan_sawah.luas_lahan_hektar) as luas_tanam_hektar'),
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
                    'petani_id' => $row->assigned_petugas_id,
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
                    'luas_tanam_hektar' => (float) $row->luas_tanam_hektar,
                    'luas_tanam_ha' => (float) $row->luas_tanam_hektar,

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

    public function getMapLahanTermonitor()
    {
        $rows = DB::table('lahan_huma')
            ->leftJoin('monitoring_kondisi', 'lahan_huma.id', '=', 'monitoring_kondisi.lahan_huma_id')
            ->select(
                'lahan_huma.id',
                'lahan_huma.nama_lahan',
                'lahan_huma.luas_lahan_hektar',
                'lahan_huma.luas_lahan_hektar as luas_tanam_hektar',
                'lahan_huma.latitude',
                'lahan_huma.longitude',
                DB::raw('ST_AsGeoJSON(lahan_huma.polygon_area) as geojson'),
                'lahan_huma.catatan_verifikasi',
                'monitoring_kondisi.catatan_petugas',
                'monitoring_kondisi.ph_air',
                'monitoring_kondisi.tanggal_cek'
            )
            // Lakukan pengelompokan jika ada multiple monitoring
            ->orderBy('monitoring_kondisi.tanggal_cek', 'desc')
            ->get()
            ->unique('id'); // Ambil latest sensor per lahan

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

            $catatanVerifikasi = json_decode($row->catatan_verifikasi ?? '{}', true);
            $catatanPetugas = json_decode($row->catatan_petugas ?? '{}', true);

            $features[] = [
                'type' => 'Feature',
                'geometry' => $geometry,
                'properties' => [
                    'id' => $row->id,
                    'nama_lahan' => $row->nama_lahan,
                    'luas_lahan_hektar' => (float) $row->luas_lahan_hektar,
                    'luas_tanam_hektar' => (float) $row->luas_tanam_hektar,
                    'sumber' => 'Huma',
                    'device_id' => $catatanVerifikasi['huma_device_id'] ?? '-',
                    'ph_tanah' => $catatanPetugas['ph_tanah'] ?? $row->ph_air ?? '-',
                    'n_level' => $catatanPetugas['n_level'] ?? '-',
                    'p_level' => $catatanPetugas['p_level'] ?? '-',
                    'k_level' => $catatanPetugas['k_level'] ?? '-',
                    'waktu_rekam' => $row->tanggal_cek ?? '-',
                    'rekomendasi_pupuk' => $catatanPetugas['rekomendasi_pupuk'] ?? []
                ]
            ];
        }

        return response()->json([
            'success' => true,
            'type' => 'FeatureCollection',
            'features' => $features,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function lahanPublikQuery()
    {
        return DB::table('lahan_sawah')
            ->where('lahan_sawah.status_verifikasi', 'DITERIMA');
    }

    private function lahanDiterimaQuery()
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
        return (float) $this->lahanDiterimaQuery()
            ->leftJoinSub($this->panenDiterimaPerLahanQuery(), 'panen_lahan', function ($join) {
                $join->on('panen_lahan.lahan_id', '=', 'lahan_sawah.id');
            })
            ->sum(DB::raw('COALESCE(panen_lahan.total_panen,0)'));
    }

    private function chartPanenKecamatan()
    {
        $statistikRows = $this->latestStatistikPadiRows();

        if ($statistikRows->isNotEmpty()) {
            return $statistikRows
                ->map(fn ($row) => [
                    'nama_kecamatan' => $row->nama_kecamatan,
                    'total_panen' => round((float) $row->produksi_ton, 2),
                    'tahun' => (int) $row->tahun,
                    'sumber' => $row->sumber_data,
                ])
                ->values();
        }

        return $this->lahanDiterimaQuery()
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoinSub($this->panenDiterimaPerLahanQuery(), 'panen_lahan', function ($join) {
                $join->on('panen_lahan.lahan_id', '=', 'lahan_sawah.id');
            })
            ->select('kecamatan.nama_kecamatan', DB::raw('ROUND(COALESCE(SUM(panen_lahan.total_panen),0), 2) as total_panen'))
            ->groupBy('kecamatan.nama_kecamatan')
            ->orderBy('kecamatan.nama_kecamatan')
            ->get();
    }

    private function chartLuasKecamatan()
    {
        $statistikRows = $this->latestStatistikPadiRows();

        if ($statistikRows->isNotEmpty()) {
            return $statistikRows
                ->map(fn ($row) => [
                    'nama_kecamatan' => $row->nama_kecamatan,
                    'total_luas' => round((float) $row->luas_panen_ha, 2),
                    'luas_tanam_ha' => round((float) $row->luas_tanam_ha, 2),
                    'tahun' => (int) $row->tahun,
                    'sumber' => $row->sumber_data,
                ])
                ->values();
        }

        return $this->lahanDiterimaQuery()
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->select(
                'kecamatan.nama_kecamatan',
                DB::raw('ROUND(COALESCE(SUM(lahan_sawah.luas_lahan_hektar),0), 2) as total_luas'),
                DB::raw('ROUND(COALESCE(SUM(COALESCE(lahan_sawah.luas_tanam_hektar, lahan_sawah.luas_lahan_hektar)),0), 2) as luas_tanam_ha')
            )
            ->groupBy('kecamatan.nama_kecamatan')
            ->orderBy('kecamatan.nama_kecamatan')
            ->get();
    }

    private function chartLuasTipeLahan()
    {
        return $this->lahanDiterimaQuery()
            ->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id')
            ->select(
                'lahan_sawah.tipe_lahan_id',
                DB::raw("COALESCE(tipe_lahan.nama_tipe, 'Belum Ditentukan') as nama_tipe"),
                DB::raw("COALESCE(tipe_lahan.nama_tipe, 'Belum Ditentukan') as tipe_lahan"),
                DB::raw('ROUND(COALESCE(SUM(lahan_sawah.luas_lahan_hektar),0), 2) as total_luas'),
                DB::raw('ROUND(COALESCE(SUM(COALESCE(lahan_sawah.luas_tanam_hektar, lahan_sawah.luas_lahan_hektar)),0), 2) as total_luas_tanam')
            )
            ->groupBy('lahan_sawah.tipe_lahan_id', 'tipe_lahan.nama_tipe')
            ->orderBy('tipe_lahan.nama_tipe')
            ->get();
    }

    private function chartProduktivitasLahan()
    {
        $statistikRows = $this->latestStatistikPadiRows();

        if ($statistikRows->isNotEmpty()) {
            return $statistikRows
                ->map(fn ($row) => [
                    'nama_lahan' => $row->nama_kecamatan,
                    'periode_label' => $row->nama_kecamatan,
                    'total_panen' => round((float) $row->produksi_ton, 2),
                    'total_luas_panen' => round((float) $row->luas_panen_ha, 2),
                    'produktivitas_ton_ha' => round((float) $row->produktivitas_ton_ha, 2),
                    'tahun' => (int) $row->tahun,
                    'sumber' => $row->sumber_data,
                ])
                ->values();
        }

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
                    DB::raw('ROUND(SUM(COALESCE(rp.luas_tanam_hektar, rp.luas_lahan_ha)), 2) as total_luas_panen'),
                    DB::raw('CASE WHEN SUM(COALESCE(rp.luas_tanam_hektar, rp.luas_lahan_ha)) > 0 THEN ROUND(SUM(rp.hasil_panen_ton) / SUM(COALESCE(rp.luas_tanam_hektar, rp.luas_lahan_ha)), 2) ELSE 0 END as produktivitas_ton_ha')
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

        return $this->lahanDiterimaQuery()
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoinSub($this->panenDiterimaPerLahanQuery(), 'panen_lahan', function ($join) {
                $join->on('panen_lahan.lahan_id', '=', 'lahan_sawah.id');
            })
            ->select(
                'kecamatan.nama_kecamatan as nama_lahan',
                DB::raw('SUM(COALESCE(panen_lahan.total_panen,0)) as total_panen'),
                DB::raw('CASE WHEN SUM(COALESCE(lahan_sawah.luas_tanam_hektar, lahan_sawah.luas_lahan_hektar)) > 0 THEN ROUND(SUM(COALESCE(panen_lahan.total_panen,0)) / SUM(COALESCE(lahan_sawah.luas_tanam_hektar, lahan_sawah.luas_lahan_hektar)), 2) ELSE 0 END as produktivitas_ton_ha')
            )
            ->groupBy('kecamatan.nama_kecamatan')
            ->orderBy('kecamatan.nama_kecamatan')
            ->get();
    }

    private function buildTabelRekap()
    {
        $rows = $this->lahanDiterimaQuery()
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
                DB::raw('COALESCE(lahan_sawah.luas_tanam_hektar, lahan_sawah.luas_lahan_hektar) as luas_tanam_hektar'),
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
                            'total_luas_tanam' => round((float) $tipeItems->sum('luas_tanam_hektar'), 2),
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
                    'total_luas_tanam' => round((float) $items->sum('luas_tanam_hektar'), 2),
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

    private function statistikPadiTableReady(): bool
    {
        return Schema::hasTable('kecamatan') && Schema::hasTable('statistik_padi_kecamatan');
    }

    private function latestStatistikPadiRows()
    {
        if (!$this->statistikPadiTableReady()) {
            return collect();
        }

        $latestYears = DB::table('statistik_padi_kecamatan')
            ->select('kecamatan_id', DB::raw('MAX(tahun) as tahun'))
            ->groupBy('kecamatan_id');

        return DB::table('statistik_padi_kecamatan as s')
            ->joinSub($latestYears, 'latest', function ($join) {
                $join->on('latest.kecamatan_id', '=', 's.kecamatan_id')
                    ->on('latest.tahun', '=', 's.tahun');
            })
            ->join('kecamatan as k', 'k.id', '=', 's.kecamatan_id')
            ->select(
                's.kecamatan_id',
                'k.nama_kecamatan',
                's.tahun',
                's.luas_tanam_ha',
                's.luas_panen_ha',
                's.produktivitas_kw_ha',
                's.produktivitas_ton_ha',
                's.produksi_ton',
                's.is_sementara',
                's.sumber_data'
            )
            ->orderBy('k.nama_kecamatan')
            ->get()
            ->map(function ($row) {
                if ($row->tahun == 2025) {
                    $row->is_sementara = 0;
                }
                return $row;
            });
    }

    private function tahunStatistikPadiOptions()
    {
        if (!$this->statistikPadiTableReady()) {
            return [];
        }

        return DB::table('statistik_padi_kecamatan')
            ->select('tahun')
            ->distinct()
            ->orderBy('tahun')
            ->pluck('tahun')
            ->map(fn ($tahun) => (int) $tahun)
            ->values();
    }

    private function buildRekapPadiKecamatan()
    {
        if (!$this->statistikPadiTableReady()) {
            return collect();
        }

        $rows = DB::table('statistik_padi_kecamatan as s')
            ->join('kecamatan as k', 'k.id', '=', 's.kecamatan_id')
            ->select(
                's.kecamatan_id',
                'k.nama_kecamatan',
                's.tahun',
                's.luas_tanam_ha',
                's.luas_panen_ha',
                's.produktivitas_kw_ha',
                's.produktivitas_ton_ha',
                's.produksi_ton',
                's.is_sementara',
                's.sumber_data'
            )
            ->orderBy('k.nama_kecamatan')
            ->orderBy('s.tahun')
            ->get();

        return $rows
            ->groupBy('kecamatan_id')
            ->map(function ($items) {
                $first = $items->first();
                $latest = $items->sortByDesc('tahun')->first();
                $totalLuasTanam = (float) $items->sum('luas_tanam_ha');
                $totalLuasPanen = (float) $items->sum('luas_panen_ha');
                $totalProduksi = (float) $items->sum('produksi_ton');
                $rataProduktivitas = $totalLuasPanen > 0
                    ? $totalProduksi / $totalLuasPanen
                    : (float) $items->avg('produktivitas_ton_ha');

                return [
                    'id' => (int) $first->kecamatan_id,
                    'kecamatan_id' => (int) $first->kecamatan_id,
                    'nama_kecamatan' => $first->nama_kecamatan,
                    'tahun_awal' => (int) $items->min('tahun'),
                    'tahun_akhir' => (int) $items->max('tahun'),
                    'jumlah_tahun' => $items->count(),
                    'total_luas_tanam_ha' => round($totalLuasTanam, 2),
                    'total_luas_panen_ha' => round($totalLuasPanen, 2),
                    'total_produksi_ton' => round($totalProduksi, 2),
                    'rata_produktivitas_ton_ha' => round($rataProduktivitas, 3),
                    'rata_produktivitas_kw_ha' => round($rataProduktivitas * 10, 2),
                    'tahun_terbaru' => (int) $latest->tahun,
                    'luas_tanam_terbaru_ha' => round((float) $latest->luas_tanam_ha, 2),
                    'luas_panen_terbaru_ha' => round((float) $latest->luas_panen_ha, 2),
                    'produktivitas_terbaru_ton_ha' => round((float) $latest->produktivitas_ton_ha, 3),
                    'produksi_terbaru_ton' => round((float) $latest->produksi_ton, 2),
                    'is_sementara' => $latest->tahun == 2025 ? false : (bool) $latest->is_sementara,
                    'sumber_data' => $latest->sumber_data,
                ];
            })
            ->sortBy('nama_kecamatan')
            ->values();
    }

    private function statistikPadiRowsForKecamatan(int $kecamatanId, ?string $tahun = null)
    {
        if (!$this->statistikPadiTableReady()) {
            return collect();
        }

        $query = DB::table('statistik_padi_kecamatan as s')
            ->where('s.kecamatan_id', $kecamatanId)
            ->select(
                's.tahun',
                's.luas_tanam_ha',
                's.luas_panen_ha',
                's.produktivitas_kw_ha',
                's.produktivitas_ton_ha',
                's.produksi_ton',
                's.is_sementara',
                's.sumber_data'
            )
            ->orderBy('s.tahun');

        if ($tahun && $tahun !== 'all') {
            $query->where('s.tahun', (int) $tahun);
        }

        return $query->get()->map(function ($row) {
            $isSementara = (int) $row->tahun === 2025 ? false : (bool) $row->is_sementara;
            return [
                'tahun' => (int) $row->tahun,
                'luas_tanam_ha' => round((float) $row->luas_tanam_ha, 2),
                'luas_panen_ha' => round((float) $row->luas_panen_ha, 2),
                'produktivitas_kw_ha' => round((float) $row->produktivitas_kw_ha, 2),
                'produktivitas_ton_ha' => round((float) $row->produktivitas_ton_ha, 3),
                'produksi_ton' => round((float) $row->produksi_ton, 2),
                'is_sementara' => $isSementara,
                'status_data' => $isSementara ? 'Sementara' : 'Tetap',
                'sumber_data' => $row->sumber_data,
            ];
        });
    }

    private function summaryStatistikPadiRows($rows): array
    {
        $rows = collect($rows);

        if ($rows->isEmpty()) {
            return [
                'jumlah_tahun' => 0,
                'tahun_awal' => null,
                'tahun_akhir' => null,
                'periode_label' => '-',
                'total_luas_tanam_ha' => 0,
                'total_luas_panen_ha' => 0,
                'total_produksi_ton' => 0,
                'rata_produktivitas_ton_ha' => 0,
                'rata_produktivitas_kw_ha' => 0,
                'tahun_terbaru' => null,
                'latest' => null,
                'ada_data_sementara' => false,
            ];
        }

        $tahunAwal = (int) $rows->min('tahun');
        $tahunAkhir = (int) $rows->max('tahun');
        $latest = $rows->sortByDesc('tahun')->first();
        $totalLuasTanam = (float) $rows->sum('luas_tanam_ha');
        $totalLuasPanen = (float) $rows->sum('luas_panen_ha');
        $totalProduksi = (float) $rows->sum('produksi_ton');
        $rataProduktivitas = $totalLuasPanen > 0
            ? $totalProduksi / $totalLuasPanen
            : (float) $rows->avg('produktivitas_ton_ha');

        return [
            'jumlah_tahun' => $rows->count(),
            'tahun_awal' => $tahunAwal,
            'tahun_akhir' => $tahunAkhir,
            'periode_label' => $tahunAwal === $tahunAkhir ? (string) $tahunAwal : "{$tahunAwal} - {$tahunAkhir}",
            'total_luas_tanam_ha' => round($totalLuasTanam, 2),
            'total_luas_panen_ha' => round($totalLuasPanen, 2),
            'total_produksi_ton' => round($totalProduksi, 2),
            'rata_produktivitas_ton_ha' => round($rataProduktivitas, 3),
            'rata_produktivitas_kw_ha' => round($rataProduktivitas * 10, 2),
            'tahun_terbaru' => (int) $latest['tahun'],
            'latest' => $latest,
            'ada_data_sementara' => $rows->contains(fn ($row) => (bool) ($row['is_sementara'] ?? false)),
        ];
    }

    private function narasiStatistikPadi(string $namaKecamatan, array $summary): string
    {
        if (($summary['jumlah_tahun'] ?? 0) === 0) {
            return "Data statistik padi untuk Kecamatan {$namaKecamatan} belum tersedia pada basis data historis.";
        }

        $status = ($summary['ada_data_sementara'] ?? false)
            ? ' Beberapa nilai bertanda sementara, sehingga masih dapat berubah mengikuti pembaruan sumber data.'
            : '';

        return "Tabel ini merangkum luas tanam, luas panen, produktivitas, dan produksi padi Kecamatan {$namaKecamatan} pada periode {$summary['periode_label']}. Total produksi pada periode ini mencapai "
            . $this->formatId($summary['total_produksi_ton']) . ' ton dari '
            . $this->formatId($summary['total_luas_panen_ha']) . ' ha luas panen, dengan rata-rata produktivitas berbobot '
            . $this->formatId($summary['rata_produktivitas_ton_ha'], 3) . ' ton/ha.' . $status;
    }

    private function resolveKecamatan(string $identifier): ?object
    {
        $decoded = urldecode($identifier);

        if (ctype_digit($decoded)) {
            return DB::table('kecamatan')
                ->select('id', 'nama_kecamatan')
                ->where('id', (int) $decoded)
                ->first();
        }

        $needle = $this->kecamatanKey($decoded);

        return DB::table('kecamatan')
            ->select('id', 'nama_kecamatan')
            ->get()
            ->first(fn ($row) => $this->kecamatanKey($row->nama_kecamatan) === $needle);
    }

    private function kecamatanKey(?string $value): string
    {
        $value = preg_replace('/^kecamatan\s+/i', '', (string) $value);
        $value = str_replace(['-', '_'], ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return strtolower(trim($value));
    }

    private function filenameSlug(string $value): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($value));
        return trim($slug, '-') ?: 'kecamatan';
    }

    private function formatId(float|int $value, int $digits = 2): string
    {
        return number_format((float) $value, $digits, ',', '.');
    }

    private function excelNumber(float|int $value, int $digits = 2): string
    {
        return number_format((float) $value, $digits, '.', '');
    }

    private function buildStatistikPadiExcelHtml(string $namaKecamatan, $rows, array $summary, ?string $tahun): string
    {
        $periode = $tahun ?: ($summary['periode_label'] ?? '-');
        $bodyRows = collect($rows)->map(function ($row) {
            return '<tr>'
                . '<td>' . e($row['tahun']) . '</td>'
                . '<td>' . $this->excelNumber($row['luas_tanam_ha']) . '</td>'
                . '<td>' . $this->excelNumber($row['luas_panen_ha']) . '</td>'
                . '<td>' . $this->excelNumber($row['produktivitas_kw_ha']) . '</td>'
                . '<td>' . $this->excelNumber($row['produktivitas_ton_ha'], 3) . '</td>'
                . '<td>' . $this->excelNumber($row['produksi_ton']) . '</td>'
                . '<td>' . e($row['status_data']) . '</td>'
                . '</tr>';
        })->implode('');

        if ($bodyRows === '') {
            $bodyRows = '<tr><td colspan="7">Data tidak tersedia</td></tr>';
        }

        return "\xEF\xBB\xBF" . '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 12px; }
        th, td { border: 1px solid #94a3b8; padding: 6px 8px; }
        th { background: #dcfce7; font-weight: bold; text-align: center; }
        .title { background: #166534; color: #ffffff; font-size: 16px; font-weight: bold; }
        .meta { background: #f8fafc; font-weight: bold; }
        .number { mso-number-format:"0.00"; }
    </style>
</head>
<body>
    <table>
        <tr><td colspan="7" class="title">STATISTIK PADI KECAMATAN ' . e(strtoupper($namaKecamatan)) . '</td></tr>
        <tr><td colspan="7" class="meta">Periode: ' . e($periode) . '</td></tr>
        <tr><td colspan="7" class="meta">Total Produksi: ' . $this->excelNumber($summary['total_produksi_ton'] ?? 0) . ' ton | Rata-rata Produktivitas: ' . $this->excelNumber($summary['rata_produktivitas_ton_ha'] ?? 0, 3) . ' ton/ha</td></tr>
        <tr>
            <th>Tahun</th>
            <th>Luas Tanam (Ha)</th>
            <th>Luas Panen (Ha)</th>
            <th>Produktivitas (Kw/Ha)</th>
            <th>Produktivitas (Ton/Ha)</th>
            <th>Produksi (Ton)</th>
            <th>Status Data</th>
        </tr>
        ' . $bodyRows . '
    </table>
</body>
</html>';
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
                'luas_tanam_ha' => 0,
                'total_luas_panen_ha' => 0,
                'total_panen_ton' => 0,
                'produktivitas_ton_ha' => 0,
                'tahun_data_padi' => null,
                'is_sementara' => false,
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
                'luas_tanam_ha' => round((float) ($statistik['luas_tanam_ha'] ?? $statistik['total_luas_ha']), 2),
                'total_luas_panen_ha' => round((float) $statistik['total_luas_panen_ha'], 2),
                'total_panen_ton' => round((float) $statistik['total_panen_ton'], 2),
                'produktivitas_ton_ha' => round((float) $statistik['produktivitas_ton_ha'], 2),
                'tahun_data_padi' => $statistik['tahun_data_padi'] ?? null,
                'is_sementara' => (bool) ($statistik['is_sementara'] ?? false),
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
        $kecamatanRows = DB::table('kecamatan')->get()->keyBy('id');
        $statistikRows = $this->latestStatistikPadiRows()->keyBy('kecamatan_id');

        $lahanRows = DB::table('lahan_sawah')
            ->select(
                'lahan_sawah.kecamatan_id',
                DB::raw('COUNT(DISTINCT lahan_sawah.id) as jumlah_lahan'),
                DB::raw('ROUND(COALESCE(SUM(lahan_sawah.luas_lahan_hektar),0), 2) as total_luas_ha'),
                DB::raw('ROUND(COALESCE(SUM(COALESCE(lahan_sawah.luas_tanam_hektar, lahan_sawah.luas_lahan_hektar)),0), 2) as luas_tanam_ha'),
                DB::raw('ROUND(COALESCE(SUM(lahan_sawah.hasil_panen_ton),0), 2) as total_panen_lahan'),
                DB::raw('CASE WHEN SUM(COALESCE(lahan_sawah.hasil_panen_ton,0)) > 0 AND SUM(COALESCE(lahan_sawah.luas_tanam_hektar, lahan_sawah.luas_lahan_hektar)) > 0 THEN ROUND(SUM(COALESCE(lahan_sawah.hasil_panen_ton,0)) / SUM(COALESCE(lahan_sawah.luas_tanam_hektar, lahan_sawah.luas_lahan_hektar)), 2) WHEN AVG(NULLIF(lahan_sawah.produktivitas_ton_ha,0)) IS NOT NULL THEN ROUND(AVG(NULLIF(lahan_sawah.produktivitas_ton_ha,0)), 2) ELSE 0 END as produktivitas_lahan')
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
                    DB::raw('ROUND(COALESCE(SUM(COALESCE(rp.luas_tanam_hektar, rp.luas_lahan_ha)),0), 2) as total_luas_panen_ha'),
                    DB::raw('CASE WHEN SUM(COALESCE(rp.hasil_panen_ton,0)) > 0 AND SUM(COALESCE(rp.luas_tanam_hektar, rp.luas_lahan_ha)) > 0 THEN ROUND(SUM(rp.hasil_panen_ton) / SUM(COALESCE(rp.luas_tanam_hektar, rp.luas_lahan_ha)), 2) WHEN AVG(NULLIF(rp.produktivitas_ton_ha,0)) IS NOT NULL THEN ROUND(AVG(NULLIF(rp.produktivitas_ton_ha,0)), 2) ELSE 0 END as produktivitas_ton_ha')
                )
                ->groupBy('ls.kecamatan_id')
                ->get()
                ->keyBy('kecamatan_id');
        }

        return $kecamatanRows
            ->keys()
            ->merge($statistikRows->keys())
            ->merge($lahanRows->keys())
            ->merge($panenRows->keys())
            ->filter()
            ->unique()
            ->mapWithKeys(function ($kecamatanId) use ($kecamatanRows, $statistikRows, $lahanRows, $panenRows) {
                $kecamatan = $kecamatanRows->get($kecamatanId);
                $statistik = $statistikRows->get($kecamatanId);
                $lahan = $lahanRows->get($kecamatanId);
                $panen = $panenRows->get($kecamatanId);

                $jumlahLahan = (int) ($lahan->jumlah_lahan ?? $panen->jumlah_lahan_panen ?? 0);

                if ($statistik) {
                    $totalLuasHa = (float) $statistik->luas_tanam_ha;
                    $totalLuasPanenHa = (float) $statistik->luas_panen_ha;
                    $totalPanenTon = (float) $statistik->produksi_ton;
                    $produktivitas = (float) $statistik->produktivitas_ton_ha;
                    $sumber = 'Statistik padi kecamatan ' . $statistik->tahun;

                    return [
                        $kecamatanId => [
                            'jumlah_lahan' => $jumlahLahan,
                            'total_luas_ha' => $totalLuasHa,
                            'luas_tanam_ha' => $totalLuasHa,
                            'total_luas_panen_ha' => $totalLuasPanenHa,
                            'total_panen_ton' => $totalPanenTon,
                            'produktivitas_ton_ha' => $produktivitas,
                            'tahun_data_padi' => (int) $statistik->tahun,
                            'is_sementara' => (bool) $statistik->is_sementara,
                            'sumber_produktivitas' => $sumber,
                        ],
                    ];
                }

                $pakaiPanen = $panen && (float) $panen->total_luas_panen_ha > 0;

                $totalLuasHa = (float) ($lahan->total_luas_ha ?? 0);
                $luasTanamHa = (float) ($lahan->luas_tanam_ha ?? $totalLuasHa);
                $totalLuasPanenHa = $pakaiPanen ? (float) $panen->total_luas_panen_ha : $luasTanamHa;
                $totalPanenTon = $pakaiPanen ? (float) $panen->total_panen_ton : (float) ($lahan->total_panen_lahan ?? 0);
                $produktivitas = $pakaiPanen
                    ? (float) $panen->produktivitas_ton_ha
                    : (float) ($lahan->produktivitas_lahan ?? 0);

                $sumber = $pakaiPanen ? 'panen_padi diterima' : ($lahan ? 'lahan_sawah' : 'Belum ada data');

                if ($totalPanenTon <= 0 && $produktivitas <= 0 && $kecamatan) {
                    $produktivitas = (float) ($kecamatan->produktivitas ?? 0);
                    if ($produktivitas > 20) {
                        $produktivitas = $produktivitas / 10;
                    }

                    $totalPanenTon = (float) ($kecamatan->produksi ?? 0);
                    $totalLuasHa = (float) ($kecamatan->luas_tanam_ha ?? $totalLuasHa);
                    $luasTanamHa = (float) ($kecamatan->luas_tanam_ha ?? $luasTanamHa);
                    $totalLuasPanenHa = (float) ($kecamatan->luas_panen_ha ?? $totalLuasPanenHa);

                    if ($produktivitas > 0 || $totalPanenTon > 0) {
                        $sumber = 'Ringkasan kecamatan';
                    }
                }

                return [
                    $kecamatanId => [
                        'jumlah_lahan' => $jumlahLahan,
                        'total_luas_ha' => $totalLuasHa,
                        'luas_tanam_ha' => $luasTanamHa,
                        'total_luas_panen_ha' => $totalLuasPanenHa,
                        'total_panen_ton' => $totalPanenTon,
                        'produktivitas_ton_ha' => $produktivitas,
                        'tahun_data_padi' => $kecamatan->tahun_data_padi ?? null,
                        'is_sementara' => false,
                        'sumber_produktivitas' => $sumber,
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
