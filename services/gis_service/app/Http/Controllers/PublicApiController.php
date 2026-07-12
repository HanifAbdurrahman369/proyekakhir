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
        try {
            $totalKecamatan = Schema::hasTable('kecamatan') ? DB::table('kecamatan')->count() : 0;
            $totalKelurahan = Schema::hasTable('kelurahan') ? DB::table('kelurahan')->count() : 0;
            $totalLahanSawah = $this->lahanDiterimaQuery()->count();
            $totalLuasHektar = $this->lahanDiterimaQuery()
                ->sum(DB::raw($this->sqlColumn('lahan_sawah', 'luas_lahan_hektar', null, '0')));
            $totalLuasTanamHektar = $this->lahanDiterimaQuery()
                ->sum(DB::raw('COALESCE(' . $this->sqlColumn('lahan_sawah', 'luas_tanam_hektar') . ', ' . $this->sqlColumn('lahan_sawah', 'luas_lahan_hektar', null, '0') . ')'));
            $totalPanen = $this->totalPanenDiterimaPublik();
            $totalLahanTermonitor = $this->totalLahanTermonitor();
            $rekapRows = $this->buildTabelRekap();
            $rekapPadiKecamatan = $this->buildRekapPadiKecamatan();

            return $this->noStore(response()->json([
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
                        'total_lahan_termonitor' => $totalLahanTermonitor,
                    ],
                    'kecamatan_all' => Schema::hasTable('kecamatan')
                        ? DB::table('kecamatan')->select('nama_kecamatan')->orderBy('nama_kecamatan')->get()
                        : collect(),
                    'kelurahan_all' => $this->kelurahanPublikRows(),
                    'lahan_all' => $this->lahanPublikRows(),
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
            ]));
        } catch (\Throwable $e) {
            report($e);

            return $this->noStore(response()->json([
                'success' => false,
                'status' => 'degraded',
                'data' => $this->emptyStatistikData(),
                'message' => 'Data statistik belum dapat dimuat lengkap, struktur respons tetap tersedia.',
            ]));
        }
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
        if (!Schema::hasTable('lahan_sawah')) {
            return $this->featureCollectionResponse([], ['message' => 'Tabel lahan_sawah belum tersedia']);
        }

        try {
            $query = $this->lahanPublikQuery();

            if (Schema::hasTable('kecamatan') && Schema::hasColumn('lahan_sawah', 'kecamatan_id')) {
                $query->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id');
            }

            if (Schema::hasTable('kelurahan') && Schema::hasColumn('lahan_sawah', 'kelurahan_id')) {
                $query->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id');
            }

            if (Schema::hasTable('tipe_lahan') && Schema::hasColumn('lahan_sawah', 'tipe_lahan_id')) {
                $query->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id');
            }

            if (Schema::hasTable('users') && Schema::hasColumn('lahan_sawah', 'pemilik_id')) {
                $query->leftJoin('users as pemilik', 'lahan_sawah.pemilik_id', '=', 'pemilik.id');
            }

            $query->leftJoinSub($this->panenDiterimaPerLahanQuery(), 'panen_lahan', function ($join) {
                $join->on('panen_lahan.lahan_id', '=', 'lahan_sawah.id');
            });

            $rows = $query
                ->select(
                    $this->selectColumn('lahan_sawah', 'id'),
                    $this->selectColumn('lahan_sawah', 'pemilik_id'),
                    $this->selectColumn('lahan_sawah', 'assigned_petugas_id'),
                    $this->selectColumn('lahan_sawah', 'kecamatan_id'),
                    $this->selectColumn('lahan_sawah', 'kelurahan_id'),
                    $this->selectColumn('lahan_sawah', 'tipe_lahan_id'),
                    $this->selectColumn('lahan_sawah', 'nama_lahan'),
                    $this->selectColumn('users', 'nama_lengkap', 'pemilik', 'pemilik_lahan'),
                    $this->selectColumn('lahan_sawah', 'tahun_lbs'),
                    $this->selectColumn('lahan_sawah', 'luas_lahan_hektar', null, null, '0'),
                    DB::raw('COALESCE(' . $this->sqlColumn('lahan_sawah', 'luas_tanam_hektar') . ', ' . $this->sqlColumn('lahan_sawah', 'luas_lahan_hektar', null, '0') . ') as luas_tanam_hektar'),
                    $this->selectColumn('lahan_sawah', 'hasil_panen_ton', null, null, '0'),
                    $this->selectColumn('lahan_sawah', 'produktivitas_ton_ha', null, null, '0'),
                    $this->selectColumn('lahan_sawah', 'alamat_detail'),
                    $this->selectColumn('lahan_sawah', 'latitude'),
                    $this->selectColumn('lahan_sawah', 'longitude'),
                    $this->selectColumn('kecamatan', 'nama_kecamatan'),
                    $this->selectColumn('kelurahan', 'nama_kelurahan'),
                    $this->selectColumn('tipe_lahan', 'nama_tipe'),
                    DB::raw('COALESCE(panen_lahan.total_panen,0) as total_panen_diterima'),
                    $this->geoJsonSelect('lahan_sawah', 'polygon_area', 'geojson')
                )
                ->get();
        } catch (\Throwable $e) {
            report($e);

            return $this->featureCollectionResponse([], ['message' => 'Data lahan sawah belum dapat dimuat']);
        }

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

        return $this->featureCollectionResponse($features);
    }

    public function getMapLahanTermonitor()
    {
        if (!Schema::hasTable('lahan_huma')) {
            return $this->featureCollectionResponse([], ['message' => 'Tabel lahan_huma belum tersedia']);
        }

        try {
            $query = DB::table('lahan_huma');

            if (Schema::hasTable('monitoring_kondisi') && Schema::hasColumn('monitoring_kondisi', 'lahan_huma_id')) {
                $query->leftJoin('monitoring_kondisi', 'lahan_huma.id', '=', 'monitoring_kondisi.lahan_huma_id');
            }

            $rows = $query
                ->select(
                    $this->selectColumn('lahan_huma', 'id'),
                    $this->selectColumn('lahan_huma', 'nama_lahan'),
                    $this->selectColumn('lahan_huma', 'luas_lahan_hektar', null, null, '0'),
                    $this->selectColumn('lahan_huma', 'luas_lahan_hektar', null, 'luas_tanam_hektar', '0'),
                    $this->selectColumn('lahan_huma', 'latitude'),
                    $this->selectColumn('lahan_huma', 'longitude'),
                    $this->geoJsonSelect('lahan_huma', 'polygon_area', 'geojson'),
                    $this->selectColumn('lahan_huma', 'device_id'),
                    $this->selectColumn('lahan_huma', 'external_id'),
                    $this->selectColumn('lahan_huma', 'nama_pemilik'),
                    $this->selectColumn('lahan_huma', 'district_name'),
                    $this->selectColumn('lahan_huma', 'tipe_tanah'),
                    $this->selectColumn('lahan_huma', 'status_verifikasi'),
                    $this->selectColumn('monitoring_kondisi', 'id', null, 'monitoring_kondisi_id'),
                    $this->selectColumn('monitoring_kondisi', 'ph_air'),
                    $this->selectColumn('monitoring_kondisi', 'tinggi_muka_air'),
                    $this->selectColumn('monitoring_kondisi', 'n_level'),
                    $this->selectColumn('monitoring_kondisi', 'p_level'),
                    $this->selectColumn('monitoring_kondisi', 'k_level'),
                    $this->selectColumn('monitoring_kondisi', 'is_shared', null, null, '0'),
                    $this->selectColumn('monitoring_kondisi', 'tanggal_cek')
                )
                ->when(
                    Schema::hasTable('monitoring_kondisi')
                    && Schema::hasColumn('monitoring_kondisi', 'lahan_huma_id')
                    && Schema::hasColumn('monitoring_kondisi', 'tanggal_cek'),
                    function ($query) {
                    $query->orderBy('monitoring_kondisi.tanggal_cek', 'desc');
                })
                ->get()
                ->unique('id');
        } catch (\Throwable $e) {
            report($e);

            return $this->featureCollectionResponse([], ['message' => 'Data lahan termonitor belum dapat dimuat']);
        }

        $monitoringIds = $rows->pluck('monitoring_kondisi_id')->filter()->toArray();
        $rekomendasiList = [];
        if (!empty($monitoringIds) && Schema::hasTable('rekomendasi_huma')) {
            $reks = DB::table('rekomendasi_huma')
                ->whereIn('monitoring_kondisi_id', $monitoringIds)
                ->orderBy('tanggal_rekomendasi', 'desc')
                ->get();

            $groupedReks = $reks->groupBy('rekomendasi_id_huma');

            foreach ($groupedReks as $rekId => $items) {
                // First item gives the header info
                $first = $items->first();
                $details = [];
                
                foreach ($items as $item) {
                    if ($item->nama_pupuk) {
                        $details[] = [
                            'fertilizer_name' => $item->nama_pupuk,
                            'dose_amount' => $item->dosis,
                            'unit' => $item->satuan,
                            'notes' => $item->catatan
                        ];
                    }
                }
                
                $rekomendasiList[$first->monitoring_kondisi_id][] = [
                    'id' => $first->rekomendasi_id_huma,
                    'date' => $first->tanggal_rekomendasi,
                    'current_ph' => $first->current_ph,
                    'current_water' => $first->current_water,
                    'current_n' => $first->current_n,
                    'current_p' => $first->current_p,
                    'current_k' => $first->current_k,
                    'water_status' => $first->water_status,
                    'status' => $first->status_tindakan,
                    'details' => $details
                ];
            }
        }

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
                    'id' => $row->id,
                    'nama_lahan' => $row->nama_lahan,
                    'luas_lahan_hektar' => (float) $row->luas_lahan_hektar,
                    'luas_tanam_hektar' => (float) $row->luas_tanam_hektar,
                    'sumber' => 'Huma',
                    'device_id' => $row->device_id ?? '-',
                    'external_id' => $row->external_id ?? '-',
                    'pemilik_lahan' => $row->nama_pemilik ?? 'Petani Huma',
                    'jenis_tanah' => $row->tipe_tanah ?? '-',
                    'district_name' => $row->district_name ?? '-',
                    'status_verifikasi' => $row->status_verifikasi ?? '-',
                    'is_shared' => $row->is_shared ? true : false,
                    'ph_tanah' => $row->ph_air ?? '-',
                    'n_level' => $row->n_level ?? '-',
                    'p_level' => $row->p_level ?? '-',
                    'k_level' => $row->k_level ?? '-',
                    'water_level' => $row->tinggi_muka_air ?? '-',
                    'waktu_rekam' => $row->tanggal_cek ?? '-',
                    'rekomendasi_pupuk' => $rekomendasiList[$row->monitoring_kondisi_id] ?? []
                ]
            ];
        }

        return $this->featureCollectionResponse($features);
    }

    private function lahanPublikQuery()
    {
        return $this->lahanDiterimaQuery();
    }

    private function lahanDiterimaQuery()
    {
        if (!Schema::hasTable('lahan_sawah')) {
            return $this->emptyLahanSawahQuery();
        }

        $query = DB::table('lahan_sawah');

        if (Schema::hasColumn('lahan_sawah', 'status_verifikasi')) {
            $query->where('lahan_sawah.status_verifikasi', 'DITERIMA');
        }

        return $query;
    }

    private function totalLahanTermonitor(): int
    {
        if (!Schema::hasTable('lahan_huma')) {
            return 0;
        }

        return DB::table('lahan_huma')->count();
    }

    private function emptyLahanSawahQuery()
    {
        return DB::query()->fromSub(function ($query) {
            $query->selectRaw(
                'NULL as id,
                NULL as pemilik_id,
                NULL as assigned_petugas_id,
                NULL as kecamatan_id,
                NULL as kelurahan_id,
                NULL as tipe_lahan_id,
                NULL as nama_lahan,
                NULL as tahun_lbs,
                0 as luas_lahan_hektar,
                0 as luas_tanam_hektar,
                0 as hasil_panen_ton,
                0 as produktivitas_ton_ha,
                NULL as alamat_detail,
                NULL as latitude,
                NULL as longitude,
                NULL as status_verifikasi,
                NULL as polygon_area'
            )->whereRaw('1 = 0');
        }, 'lahan_sawah');
    }

    private function noStore($response)
    {
        return $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    private function featureCollectionResponse(array $features = [], array $meta = [])
    {
        $collection = [
            'type' => 'FeatureCollection',
            'features' => array_values($features),
        ];

        if (!empty($meta)) {
            $collection['meta'] = $meta;
        }

        return $this->noStore(response()->json([
            'success' => true,
            'type' => 'FeatureCollection',
            'features' => $collection['features'],
            'meta' => $collection['meta'] ?? null,
            'data' => $collection,
        ]));
    }

    private function emptyStatistikData(): array
    {
        return [
            'summary' => [
                'total_kecamatan' => 0,
                'total_kelurahan' => 0,
                'total_lahan_sawah' => 0,
                'total_luas_ha' => 0,
                'total_luas_tanam_ha' => 0,
                'total_panen_ton' => 0,
                'total_lahan_termonitor' => 0,
            ],
            'kecamatan_all' => [],
            'kelurahan_all' => [],
            'lahan_all' => [],
            'chart_panen_kecamatan' => [],
            'chart_luas_tipe_lahan' => [],
            'chart_produktivitas_lahan' => [],
            'chart_luas_kecamatan' => [],
            'tipe_lahan_options' => [],
            'tabel_rekap' => [],
            'rekap_padi_kecamatan' => [],
            'tahun_padi_options' => [],
        ];
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function sqlColumn(string $schemaTable, string $column, ?string $queryRef = null, string $default = 'NULL'): string
    {
        if (!Schema::hasTable($schemaTable) || !Schema::hasColumn($schemaTable, $column)) {
            return $default;
        }

        return $this->quoteIdentifier($queryRef ?: $schemaTable) . '.' . $this->quoteIdentifier($column);
    }

    private function selectColumn(
        string $schemaTable,
        string $column,
        ?string $queryRef = null,
        ?string $alias = null,
        string $default = 'NULL'
    ) {
        $alias ??= $column;

        if (!Schema::hasTable($schemaTable) || !Schema::hasColumn($schemaTable, $column)) {
            return DB::raw($default . ' as ' . $this->quoteIdentifier($alias));
        }

        $sql = $this->sqlColumn($schemaTable, $column, $queryRef);

        if ($alias !== $column || $queryRef) {
            $sql .= ' as ' . $this->quoteIdentifier($alias);
        }

        return DB::raw($sql);
    }

    private function geoJsonSelect(string $table, string $column, string $alias)
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return DB::raw('NULL as ' . $this->quoteIdentifier($alias));
        }

        return DB::raw('ST_AsGeoJSON(' . $this->sqlColumn($table, $column) . ') as ' . $this->quoteIdentifier($alias));
    }

    private function canJoinPanenToLahan(): bool
    {
        if (!Schema::hasTable('panen_padi') || !Schema::hasTable('lahan_sawah')) {
            return false;
        }

        if (Schema::hasColumn('panen_padi', 'lahan_id')) {
            return true;
        }

        return Schema::hasColumn('panen_padi', 'tanam_padi_id')
            && Schema::hasTable('tanam_padi')
            && Schema::hasColumn('tanam_padi', 'id')
            && Schema::hasColumn('tanam_padi', 'lahan_id');
    }

    private function panenUsesTanamJoin(): bool
    {
        return !Schema::hasColumn('panen_padi', 'lahan_id')
            && Schema::hasColumn('panen_padi', 'tanam_padi_id')
            && Schema::hasTable('tanam_padi');
    }

    private function joinPanenToLahan($query): void
    {
        if (Schema::hasColumn('panen_padi', 'lahan_id')) {
            $query->join('lahan_sawah as ls', 'ls.id', '=', 'rp.lahan_id');
            return;
        }

        $query
            ->join('tanam_padi as tp', 'tp.id', '=', 'rp.tanam_padi_id')
            ->join('lahan_sawah as ls', 'ls.id', '=', 'tp.lahan_id');
    }

    private function panenLahanIdSql(): string
    {
        return Schema::hasColumn('panen_padi', 'lahan_id')
            ? $this->sqlColumn('panen_padi', 'lahan_id', 'rp')
            : $this->sqlColumn('tanam_padi', 'lahan_id', 'tp');
    }

    private function panenLuasTanamSql(): string
    {
        $columns = [
            $this->sqlColumn('panen_padi', 'luas_tanam_hektar', 'rp'),
            $this->sqlColumn('panen_padi', 'luas_lahan_ha', 'rp'),
        ];

        if ($this->panenUsesTanamJoin()) {
            $columns[] = $this->sqlColumn('tanam_padi', 'luas_tanam_hektar', 'tp');
        }

        $columns[] = $this->sqlColumn('lahan_sawah', 'luas_tanam_hektar', 'ls');
        $columns[] = $this->sqlColumn('lahan_sawah', 'luas_lahan_hektar', 'ls', '0');

        return 'COALESCE(' . implode(', ', $columns) . ')';
    }

    private function kelurahanPublikRows()
    {
        if (!Schema::hasTable('kelurahan')) {
            return collect();
        }

        $query = DB::table('kelurahan');

        if (Schema::hasTable('kecamatan') && Schema::hasColumn('kelurahan', 'kecamatan_id')) {
            $query->leftJoin('kecamatan', 'kelurahan.kecamatan_id', '=', 'kecamatan.id');
        }

        return $query
            ->select(
                $this->selectColumn('kelurahan', 'nama_kelurahan'),
                $this->selectColumn('kecamatan', 'nama_kecamatan')
            )
            ->orderBy('kelurahan.nama_kelurahan')
            ->get();
    }

    private function lahanPublikRows()
    {
        if (!Schema::hasTable('lahan_sawah')) {
            return collect();
        }

        $query = $this->lahanDiterimaQuery();

        if (Schema::hasTable('kecamatan') && Schema::hasColumn('lahan_sawah', 'kecamatan_id')) {
            $query->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id');
        }

        if (Schema::hasTable('kelurahan') && Schema::hasColumn('lahan_sawah', 'kelurahan_id')) {
            $query->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id');
        }

        if (Schema::hasTable('tipe_lahan') && Schema::hasColumn('lahan_sawah', 'tipe_lahan_id')) {
            $query->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id');
        }

        if (Schema::hasTable('users') && Schema::hasColumn('lahan_sawah', 'pemilik_id')) {
            $query->leftJoin('users as pemilik', 'lahan_sawah.pemilik_id', '=', 'pemilik.id');
        }

        return $query
            ->select(
                $this->selectColumn('lahan_sawah', 'id'),
                $this->selectColumn('lahan_sawah', 'nama_lahan'),
                $this->selectColumn('lahan_sawah', 'luas_lahan_hektar', null, 'luas', '0'),
                DB::raw('COALESCE(' . $this->sqlColumn('lahan_sawah', 'luas_tanam_hektar') . ', ' . $this->sqlColumn('lahan_sawah', 'luas_lahan_hektar', null, '0') . ') as luas_tanam'),
                $this->selectColumn('kecamatan', 'nama_kecamatan'),
                $this->selectColumn('kelurahan', 'nama_kelurahan'),
                $this->selectColumn('users', 'nama_lengkap', 'pemilik', 'pemilik_nama'),
                DB::raw('COALESCE(' . $this->sqlColumn('tipe_lahan', 'nama_tipe') . ", 'Belum Ditentukan') as tipe_lahan")
            )
            ->when(Schema::hasTable('kecamatan') && Schema::hasColumn('lahan_sawah', 'kecamatan_id'), fn ($query) => $query->orderBy('kecamatan.nama_kecamatan'))
            ->when(Schema::hasTable('kelurahan') && Schema::hasColumn('lahan_sawah', 'kelurahan_id'), fn ($query) => $query->orderBy('kelurahan.nama_kelurahan'))
            ->when(Schema::hasColumn('lahan_sawah', 'nama_lahan'), fn ($query) => $query->orderBy('lahan_sawah.nama_lahan'))
            ->get();
    }

    private function panenDiterimaPerLahanQuery()
    {
        if ($this->canJoinPanenToLahan()) {
            $query = DB::table('panen_padi as rp');
            $this->joinPanenToLahan($query);

            $query->select(
                DB::raw($this->panenLahanIdSql() . ' as lahan_id'),
                DB::raw('COALESCE(SUM(' . $this->sqlColumn('panen_padi', 'hasil_panen_ton', 'rp', '0') . '),0) as total_panen')
            );

            if (Schema::hasColumn('panen_padi', 'status_verifikasi')) {
                $query->where('rp.status_verifikasi', 'DITERIMA');
            }

            if (Schema::hasColumn('panen_padi', 'tanggal_panen')) {
                $query->whereDate('rp.tanggal_panen', '<=', now()->toDateString());
            }

            return $query->groupByRaw($this->panenLahanIdSql());
        }

        return $this->emptyLahanSawahQuery()
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

        $query = $this->lahanDiterimaQuery();
        $hasKecamatanJoin = Schema::hasTable('kecamatan') && Schema::hasColumn('lahan_sawah', 'kecamatan_id');

        if ($hasKecamatanJoin) {
            $query->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id');
        }

        $query
            ->leftJoinSub($this->panenDiterimaPerLahanQuery(), 'panen_lahan', function ($join) {
                $join->on('panen_lahan.lahan_id', '=', 'lahan_sawah.id');
            })
            ->select(
                $hasKecamatanJoin
                    ? DB::raw("COALESCE(kecamatan.nama_kecamatan, 'Belum Ditentukan') as nama_kecamatan")
                    : DB::raw("'Belum Ditentukan' as nama_kecamatan"),
                DB::raw('ROUND(COALESCE(SUM(panen_lahan.total_panen),0), 2) as total_panen')
            );

        if ($hasKecamatanJoin) {
            $query->groupBy('kecamatan.nama_kecamatan')->orderBy('kecamatan.nama_kecamatan');
        }

        return $query->get();
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

        $query = $this->lahanDiterimaQuery();
        $hasKecamatanJoin = Schema::hasTable('kecamatan') && Schema::hasColumn('lahan_sawah', 'kecamatan_id');

        if ($hasKecamatanJoin) {
            $query->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id');
        }

        $query->select(
            $hasKecamatanJoin
                ? DB::raw("COALESCE(kecamatan.nama_kecamatan, 'Belum Ditentukan') as nama_kecamatan")
                : DB::raw("'Belum Ditentukan' as nama_kecamatan"),
            DB::raw('ROUND(COALESCE(SUM(' . $this->sqlColumn('lahan_sawah', 'luas_lahan_hektar', null, '0') . '),0), 2) as total_luas'),
            DB::raw('ROUND(COALESCE(SUM(COALESCE(' . $this->sqlColumn('lahan_sawah', 'luas_tanam_hektar') . ', ' . $this->sqlColumn('lahan_sawah', 'luas_lahan_hektar', null, '0') . ')),0), 2) as luas_tanam_ha')
        );

        if ($hasKecamatanJoin) {
            $query->groupBy('kecamatan.nama_kecamatan')->orderBy('kecamatan.nama_kecamatan');
        }

        return $query->get();
    }

    private function chartLuasTipeLahan()
    {
        $query = $this->lahanDiterimaQuery();
        $hasTipeId = Schema::hasColumn('lahan_sawah', 'tipe_lahan_id');
        $hasTipeJoin = Schema::hasTable('tipe_lahan') && $hasTipeId;

        if ($hasTipeJoin) {
            $query->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id');
        }

        $namaTipeSql = $hasTipeJoin ? "COALESCE(tipe_lahan.nama_tipe, 'Belum Ditentukan')" : "'Belum Ditentukan'";

        $query->select(
            DB::raw($this->sqlColumn('lahan_sawah', 'tipe_lahan_id') . ' as tipe_lahan_id'),
            DB::raw($namaTipeSql . ' as nama_tipe'),
            DB::raw($namaTipeSql . ' as tipe_lahan'),
            DB::raw('ROUND(COALESCE(SUM(' . $this->sqlColumn('lahan_sawah', 'luas_lahan_hektar', null, '0') . '),0), 2) as total_luas'),
            DB::raw('ROUND(COALESCE(SUM(COALESCE(' . $this->sqlColumn('lahan_sawah', 'luas_tanam_hektar') . ', ' . $this->sqlColumn('lahan_sawah', 'luas_lahan_hektar', null, '0') . ')),0), 2) as total_luas_tanam')
        );

        if ($hasTipeJoin) {
            $query->groupBy('lahan_sawah.tipe_lahan_id', 'tipe_lahan.nama_tipe')->orderBy('tipe_lahan.nama_tipe');
        } elseif ($hasTipeId) {
            $query->groupBy('lahan_sawah.tipe_lahan_id')->orderBy('lahan_sawah.tipe_lahan_id');
        }

        return $query->get();
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

        if ($this->canJoinPanenToLahan()) {
            $query = DB::table('panen_padi as rp');
            $this->joinPanenToLahan($query);

            $hasKecamatanJoin = Schema::hasTable('kecamatan') && Schema::hasColumn('lahan_sawah', 'kecamatan_id');

            if ($hasKecamatanJoin) {
                $query->leftJoin('kecamatan', 'ls.kecamatan_id', '=', 'kecamatan.id');
            }

            if (Schema::hasColumn('panen_padi', 'status_verifikasi')) {
                $query->where('rp.status_verifikasi', 'DITERIMA');
            }

            if (Schema::hasColumn('panen_padi', 'tanggal_panen')) {
                $query->whereDate('rp.tanggal_panen', '<=', now()->toDateString());
            }

            if (Schema::hasColumn('lahan_sawah', 'status_verifikasi')) {
                $query->where('ls.status_verifikasi', 'DITERIMA');
            }

            $hasilPanenSql = $this->sqlColumn('panen_padi', 'hasil_panen_ton', 'rp', '0');
            $luasPanenSql = $this->panenLuasTanamSql();

            $query->select(
                $hasKecamatanJoin
                    ? DB::raw("COALESCE(kecamatan.nama_kecamatan, 'Belum Ditentukan') as nama_lahan")
                    : DB::raw("'Belum Ditentukan' as nama_lahan"),
                DB::raw('ROUND(COALESCE(SUM(' . $hasilPanenSql . '),0), 2) as total_panen'),
                DB::raw('ROUND(COALESCE(SUM(' . $luasPanenSql . '),0), 2) as total_luas_panen'),
                DB::raw('CASE WHEN SUM(' . $luasPanenSql . ') > 0 THEN ROUND(SUM(' . $hasilPanenSql . ') / SUM(' . $luasPanenSql . '), 2) ELSE 0 END as produktivitas_ton_ha')
            );

            if ($hasKecamatanJoin) {
                $query->groupBy('kecamatan.nama_kecamatan')->orderBy('kecamatan.nama_kecamatan');
            }

            return $query
                ->get()
                ->map(function ($row) {
                    $row->periode_label = $row->nama_lahan ?: 'Belum Ditentukan';
                    $row->nama_lahan = $row->periode_label;
                    return $row;
                });
        }

        $query = $this->lahanDiterimaQuery();
        $hasKecamatanJoin = Schema::hasTable('kecamatan') && Schema::hasColumn('lahan_sawah', 'kecamatan_id');
        $luasTanamSql = 'COALESCE(' . $this->sqlColumn('lahan_sawah', 'luas_tanam_hektar') . ', ' . $this->sqlColumn('lahan_sawah', 'luas_lahan_hektar', null, '0') . ')';

        if ($hasKecamatanJoin) {
            $query->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id');
        }

        $query
            ->leftJoinSub($this->panenDiterimaPerLahanQuery(), 'panen_lahan', function ($join) {
                $join->on('panen_lahan.lahan_id', '=', 'lahan_sawah.id');
            })
            ->select(
                $hasKecamatanJoin
                    ? DB::raw("COALESCE(kecamatan.nama_kecamatan, 'Belum Ditentukan') as nama_lahan")
                    : DB::raw("'Belum Ditentukan' as nama_lahan"),
                DB::raw('ROUND(COALESCE(SUM(panen_lahan.total_panen),0), 2) as total_panen'),
                DB::raw('ROUND(COALESCE(SUM(' . $luasTanamSql . '),0), 2) as total_luas_panen'),
                DB::raw('CASE WHEN SUM(' . $luasTanamSql . ') > 0 THEN ROUND(SUM(COALESCE(panen_lahan.total_panen,0)) / SUM(' . $luasTanamSql . '), 2) ELSE 0 END as produktivitas_ton_ha')
            );

        if ($hasKecamatanJoin) {
            $query->groupBy('kecamatan.nama_kecamatan')->orderBy('kecamatan.nama_kecamatan');
        }

        return $query->get();
    }

    private function buildTabelRekap()
    {
        $query = $this->lahanDiterimaQuery();
        $hasKecamatanJoin = Schema::hasTable('kecamatan') && Schema::hasColumn('lahan_sawah', 'kecamatan_id');
        $hasKelurahanJoin = Schema::hasTable('kelurahan') && Schema::hasColumn('lahan_sawah', 'kelurahan_id');
        $hasTipeJoin = Schema::hasTable('tipe_lahan') && Schema::hasColumn('lahan_sawah', 'tipe_lahan_id');
        $luasTanamSql = 'COALESCE(' . $this->sqlColumn('lahan_sawah', 'luas_tanam_hektar') . ', ' . $this->sqlColumn('lahan_sawah', 'luas_lahan_hektar', null, '0') . ')';

        if ($hasKecamatanJoin) {
            $query->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id');
        }

        if ($hasKelurahanJoin) {
            $query->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id');
        }

        if ($hasTipeJoin) {
            $query->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id');
        }

        $rows = $query
            ->leftJoinSub($this->panenDiterimaPerLahanQuery(), 'panen_lahan', function ($join) {
                $join->on('panen_lahan.lahan_id', '=', 'lahan_sawah.id');
            })
            ->select(
                $this->selectColumn('lahan_sawah', 'id'),
                $this->selectColumn('lahan_sawah', 'tahun_lbs'),
                $this->selectColumn('lahan_sawah', 'tipe_lahan_id'),
                $this->selectColumn('lahan_sawah', 'luas_lahan_hektar', null, null, '0'),
                DB::raw($luasTanamSql . ' as luas_tanam_hektar'),
                $hasKecamatanJoin
                    ? DB::raw("COALESCE(kecamatan.nama_kecamatan, '-') as nama_kecamatan")
                    : DB::raw("'-' as nama_kecamatan"),
                $hasKelurahanJoin
                    ? DB::raw("COALESCE(kelurahan.nama_kelurahan, '-') as nama_kelurahan")
                    : DB::raw("'-' as nama_kelurahan"),
                $hasTipeJoin
                    ? DB::raw("COALESCE(tipe_lahan.nama_tipe, 'Belum Ditentukan') as nama_tipe")
                    : DB::raw("'Belum Ditentukan' as nama_tipe"),
                DB::raw('COALESCE(panen_lahan.total_panen,0) as total_panen_lahan')
            );

        if ($hasKecamatanJoin) {
            $rows->orderBy('kecamatan.nama_kecamatan');
        }

        if ($hasKelurahanJoin) {
            $rows->orderBy('kelurahan.nama_kelurahan');
        }

        $rows = $rows->get();

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
        if (!Schema::hasTable('kabupaten') || !Schema::hasColumn('kabupaten', 'polygon_baritokuala')) {
            return $this->featureCollectionResponse([], [
                'message' => 'Tabel atau kolom polygon kabupaten belum tersedia',
            ]);
        }

        $kabupaten = DB::table('kabupaten')
            ->where('nama_kabupaten', 'LIKE', '%Barito Kuala%')
            ->select('polygon_baritokuala')
            ->first();

        if (!$kabupaten || !$kabupaten->polygon_baritokuala) {
            return $this->featureCollectionResponse([], [
                'message' => 'Data polygon Barito Kuala belum tersedia',
            ]);
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

        return $this->noStore(response($rawJson, 200)->header('Content-Type', 'application/json'));
    }

    public function getBatasKecamatan()
    {
        if (!Schema::hasTable('kecamatan') || !Schema::hasColumn('kecamatan', 'polygon_geojson')) {
            return $this->featureCollectionResponse([], [
                'jumlah_kecamatan' => 0,
                'jumlah_feature' => 0,
                'message' => 'Tabel atau kolom polygon kecamatan belum tersedia',
            ]);
        }

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

        return $this->noStore(response()->json([
            'success' => true,
            'type' => 'FeatureCollection',
            'features' => $features,
            'meta' => $meta,
            'data' => [
                'type' => 'FeatureCollection',
                'features' => $features,
                'meta' => $meta,
            ],
        ]));
    }

    private function agregatProduktivitasKecamatan(): array
    {
        if (!Schema::hasTable('kecamatan')) {
            return [];
        }

        $kecamatanRows = DB::table('kecamatan')->get()->keyBy('id');
        $statistikRows = $this->latestStatistikPadiRows()->keyBy('kecamatan_id');

        $lahanRows = collect();

        if (Schema::hasTable('lahan_sawah') && Schema::hasColumn('lahan_sawah', 'kecamatan_id')) {
            $lahanRows = $this->lahanDiterimaQuery()
                ->select(
                    'lahan_sawah.kecamatan_id',
                    DB::raw('COUNT(DISTINCT lahan_sawah.id) as jumlah_lahan'),
                    DB::raw('ROUND(COALESCE(SUM(' . $this->sqlColumn('lahan_sawah', 'luas_lahan_hektar', null, '0') . '),0), 2) as total_luas_ha'),
                    DB::raw('ROUND(COALESCE(SUM(COALESCE(' . $this->sqlColumn('lahan_sawah', 'luas_tanam_hektar') . ', ' . $this->sqlColumn('lahan_sawah', 'luas_lahan_hektar', null, '0') . ')),0), 2) as luas_tanam_ha'),
                    DB::raw('ROUND(COALESCE(SUM(' . $this->sqlColumn('lahan_sawah', 'hasil_panen_ton', null, '0') . '),0), 2) as total_panen_lahan'),
                    DB::raw('CASE WHEN SUM(COALESCE(' . $this->sqlColumn('lahan_sawah', 'hasil_panen_ton', null, '0') . ',0)) > 0 AND SUM(COALESCE(' . $this->sqlColumn('lahan_sawah', 'luas_tanam_hektar') . ', ' . $this->sqlColumn('lahan_sawah', 'luas_lahan_hektar', null, '0') . ')) > 0 THEN ROUND(SUM(COALESCE(' . $this->sqlColumn('lahan_sawah', 'hasil_panen_ton', null, '0') . ',0)) / SUM(COALESCE(' . $this->sqlColumn('lahan_sawah', 'luas_tanam_hektar') . ', ' . $this->sqlColumn('lahan_sawah', 'luas_lahan_hektar', null, '0') . ')), 2) WHEN AVG(NULLIF(' . $this->sqlColumn('lahan_sawah', 'produktivitas_ton_ha', null, '0') . ',0)) IS NOT NULL THEN ROUND(AVG(NULLIF(' . $this->sqlColumn('lahan_sawah', 'produktivitas_ton_ha', null, '0') . ',0)), 2) ELSE 0 END as produktivitas_lahan')
                )
                ->whereNotNull('lahan_sawah.kecamatan_id')
                ->groupBy('lahan_sawah.kecamatan_id')
                ->get()
                ->keyBy('kecamatan_id');
        }

        $panenRows = collect();

        if ($this->canJoinPanenToLahan() && Schema::hasColumn('lahan_sawah', 'kecamatan_id')) {
            $query = DB::table('panen_padi as rp');
            $this->joinPanenToLahan($query);

            if (Schema::hasColumn('panen_padi', 'status_verifikasi')) {
                $query->where('rp.status_verifikasi', 'DITERIMA');
            }

            if (Schema::hasColumn('panen_padi', 'tanggal_panen')) {
                $query->whereDate('rp.tanggal_panen', '<=', now()->toDateString());
            }

            if (Schema::hasColumn('lahan_sawah', 'status_verifikasi')) {
                $query->where('ls.status_verifikasi', 'DITERIMA');
            }

            $hasilPanenSql = $this->sqlColumn('panen_padi', 'hasil_panen_ton', 'rp', '0');
            $luasPanenSql = $this->panenLuasTanamSql();
            $produktivitasPanenSql = $this->sqlColumn('panen_padi', 'produktivitas_ton_ha', 'rp');

            $panenRows = $query
                ->whereNotNull('ls.kecamatan_id')
                ->select(
                    'ls.kecamatan_id',
                    DB::raw('COUNT(DISTINCT ls.id) as jumlah_lahan_panen'),
                    DB::raw('ROUND(COALESCE(SUM(' . $hasilPanenSql . '),0), 2) as total_panen_ton'),
                    DB::raw('ROUND(COALESCE(SUM(' . $luasPanenSql . '),0), 2) as total_luas_panen_ha'),
                    DB::raw('CASE WHEN SUM(COALESCE(' . $hasilPanenSql . ',0)) > 0 AND SUM(' . $luasPanenSql . ') > 0 THEN ROUND(SUM(' . $hasilPanenSql . ') / SUM(' . $luasPanenSql . '), 2) WHEN AVG(NULLIF(' . $produktivitasPanenSql . ',0)) IS NOT NULL THEN ROUND(AVG(NULLIF(' . $produktivitasPanenSql . ',0)), 2) ELSE 0 END as produktivitas_ton_ha')
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
