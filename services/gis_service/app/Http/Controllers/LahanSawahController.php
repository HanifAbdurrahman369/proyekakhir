<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class LahanSawahController extends Controller
{
    public function index(Request $request)
    {
        $unionQuery = $this->baseLahanQuery();
        
        $query = DB::table(DB::raw("({$unionQuery->toSql()}) as combined"))
            ->mergeBindings($unionQuery);

        $status = strtoupper((string) $request->query('status', 'DITERIMA'));
        if ($status !== 'ALL') {
            $query->where('status_verifikasi', $status);
        }

        if ($request->filled('kecamatan_id')) {
            $query->where('kecamatan_id', $request->query('kecamatan_id'));
        }

        if ($request->filled('kelurahan_id')) {
            $query->where('kelurahan_id', $request->query('kelurahan_id'));
        }

        $rows = $query
            ->orderByRaw("CASE WHEN status_verifikasi = 'DITERIMA' THEN 0 ELSE 1 END")
            ->orderBy('nama_lahan')
            ->get()
            ->map(fn ($row) => $this->normalisasiLahan($row))
            ->values()
            ->all();

        if ($request->filled('status_spasial')) {
            $target = strtoupper((string) $request->query('status_spasial'));
            $rows = collect($rows)
                ->filter(fn ($row) => ($row['status_spasial'] ?? '') === $target)
                ->values()
                ->all();
        }

        $rows = $this->beriNomorUrut($rows);

        return response()->json([
            'success' => true,
            'message' => 'Data spasial lahan berhasil diambil.',
            'summary' => $this->buildSummary($rows),
            'data' => $rows,
            'feature_collection' => $this->buildFeatureCollection($rows),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function getReferensiData()
    {
        return response()->json([
            'success' => true,
            'message' => 'Referensi data spasial berhasil diambil.',
            'data' => [
                'petani' => $this->ambilPetani(),
                'kecamatan' => $this->ambilTabel('kecamatan', ['id', 'nama_kecamatan'], 'nama_kecamatan'),
                'kelurahan' => $this->ambilTabel('kelurahan', ['id', 'kecamatan_id', 'nama_kelurahan'], 'nama_kelurahan'),
                'tipe_lahan' => $this->ambilTabel('tipe_lahan', ['id', 'nama_tipe'], 'nama_tipe'),
            ],
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function store(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'Lahan baru wajib diajukan oleh Kelompok Tani dan disetujui petugas sebelum dipetakan.',
        ], 403);
    }

    public function update(Request $request, $id)
    {
        $isHuma = str_starts_with($id, 'H-');
        $realId = (int) str_replace(['S-', 'H-'], '', $id);
        $tableName = $isHuma ? 'lahan_huma' : 'lahan_sawah';

        $existing = DB::table($tableName)
            ->where('id', $realId)
            ->select('status_verifikasi')
            ->first();

        if (!$existing) {
            return response()->json([
                'success' => false,
                'message' => 'Data lahan tidak ditemukan.',
            ], 404);
        }

        [$payload, $geometry] = $this->validasiPayloadSpasial($request, false);

        if (($existing->status_verifikasi ?? null) !== 'DITOLAK') {
            $payload['status_verifikasi'] = 'DITERIMA';
        }

        $payload['status_spasial'] = 'SUDAH_DIPETAKAN';

        if (Schema::hasColumn($tableName, 'updated_at')) {
            $payload['updated_at'] = now();
        }

        try {
            DB::transaction(function () use ($realId, $tableName, $payload, $geometry) {
                DB::table($tableName)
                    ->where('id', $realId)
                    ->update($this->filterExistingColumns($tableName, $payload));

                $this->simpanPolygonWajib($realId, $geometry, $tableName);
            });

            return response()->json([
                'success' => true,
                'message' => 'Data polygon dan informasi spasial lahan berhasil diperbarui.',
                'data' => $this->getDetailLahan($id),
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui data spasial lahan: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data spasial lahan. Pastikan polygon valid.',
            ], 422);
        }
    }

    public function destroy($id)
    {
        $isHuma = str_starts_with($id, 'H-');
        $realId = (int) str_replace(['S-', 'H-'], '', $id);
        $tableName = $isHuma ? 'lahan_huma' : 'lahan_sawah';

        if (!DB::table($tableName)->where('id', $realId)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Data lahan tidak ditemukan.',
            ], 404);
        }

        try {
            DB::transaction(function () use ($realId, $tableName) {
                if (Schema::hasColumn($tableName, 'polygon_area')) {
                    DB::statement("UPDATE $tableName SET polygon_area = NULL WHERE id = ?", [$realId]);
                }

                $payload = [
                    'latitude' => null,
                    'longitude' => null,
                    'koordinat_tengah' => null,
                    'status_spasial' => 'BELUM_DIPETAKAN',
                ];

                if (Schema::hasColumn($tableName, 'polygon_geojson')) {
                    $payload['polygon_geojson'] = null;
                }

                if (Schema::hasColumn($tableName, 'spasial_updated_at')) {
                    $payload['spasial_updated_at'] = now();
                }

                if (Schema::hasColumn($tableName, 'updated_at')) {
                    $payload['updated_at'] = now();
                }

                DB::table($tableName)
                    ->where('id', $realId)
                    ->update($this->filterExistingColumns($tableName, $payload));
            });

            return response()->json([
                'success' => true,
                'message' => 'Polygon lahan berhasil dikosongkan. Arsip lahan tetap tersimpan.',
                'data' => $this->getDetailLahan($id),
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Throwable $e) {
            Log::error('Gagal mengosongkan polygon lahan: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengosongkan polygon lahan.',
            ], 422);
        }
    }

    private function validasiPayloadSpasial(Request $request, bool $isCreate): array
    {
        $request->validate([
            'user_id' => 'nullable|integer',
            'pemilik_id' => 'nullable|integer',
            'petani_id' => 'nullable|integer',
            'kecamatan_id' => 'required|integer',
            'kelurahan_id' => 'nullable|integer',
            'tipe_lahan_id' => 'nullable|integer',
            'nama_lahan' => 'required|string|max:100',
            'tahun_lbs' => 'nullable|in:2017,2024',
            'luas_lahan_hektar' => 'required|numeric|min:0.0001',
            'alamat_detail' => 'nullable|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'polygon_geojson' => 'required|string',
            'spasial_updated_by' => 'nullable|integer',
        ]);

        $geometry = $this->validasiGeoJson($request->input('polygon_geojson'));
        $normalizedGeoJson = json_encode($geometry, JSON_UNESCAPED_UNICODE);

        $lat = (float) $request->input('latitude');
        $lng = (float) $request->input('longitude');
        $pemilikId = $request->input('pemilik_id', $request->input('user_id'));

        if ($isCreate && !$pemilikId) {
            throw ValidationException::withMessages(['pemilik_id' => 'Pemilik Kelompok Tani wajib dipilih.']);
        }
        if ($pemilikId && !DB::table('users')->where('id', $pemilikId)->where('role_id', 1)->exists()) {
            throw ValidationException::withMessages(['pemilik_id' => 'Pemilik lahan wajib memiliki role Kelompok Tani.']);
        }

        $petaniId = $request->input('petani_id', $pemilikId);
        if ($petaniId && !DB::table('users')->where('id', $petaniId)->whereIn('role_id', [1, 5])->exists()) {
            throw ValidationException::withMessages(['petani_id' => 'Petani wajib berasal dari Kelompok Tani atau Brigade Pangan.']);
        }

        $payload = [
            'pemilik_id' => $pemilikId,
            'petani_id' => $petaniId,
            'kecamatan_id' => $request->input('kecamatan_id'),
            'kelurahan_id' => $request->input('kelurahan_id'),
            'tipe_lahan_id' => $request->input('tipe_lahan_id'),
            'nama_lahan' => $request->input('nama_lahan'),
            'tahun_lbs' => $request->input('tahun_lbs', '2024'),
            'luas_lahan_hektar' => $request->input('luas_lahan_hektar'),
            'alamat_detail' => $request->input('alamat_detail'),
            'koordinat_tengah' => $lat . ',' . $lng,
            'latitude' => $lat,
            'longitude' => $lng,
        ];

        if (Schema::hasColumn('lahan_sawah', 'polygon_geojson')) {
            $payload['polygon_geojson'] = $normalizedGeoJson;
        }

        if ($request->filled('spasial_updated_by') && Schema::hasColumn('lahan_sawah', 'spasial_updated_by')) {
            $payload['spasial_updated_by'] = $request->input('spasial_updated_by');
        }

        if (Schema::hasColumn('lahan_sawah', 'spasial_updated_at')) {
            $payload['spasial_updated_at'] = now();
        }

        if (!$isCreate && !$request->filled('user_id') && !$request->filled('pemilik_id')) {
            unset($payload['pemilik_id']);
        }
        if (!$isCreate && !$request->filled('petani_id') && !$request->filled('user_id')) {
            unset($payload['petani_id']);
        }

        return [$payload, $geometry];
    }

    private function validasiGeoJson(string $raw): array
    {
        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw ValidationException::withMessages([
                'polygon_geojson' => 'Polygon GeoJSON tidak valid.',
            ]);
        }

        $geometry = ($decoded['type'] ?? null) === 'Feature'
            ? ($decoded['geometry'] ?? null)
            : $decoded;

        if (!is_array($geometry) || !in_array($geometry['type'] ?? null, ['Polygon', 'MultiPolygon'], true)) {
            throw ValidationException::withMessages([
                'polygon_geojson' => 'Polygon wajib berupa GeoJSON Polygon atau MultiPolygon.',
            ]);
        }

        $rings = $geometry['type'] === 'Polygon'
            ? ($geometry['coordinates'] ?? [])
            : ($geometry['coordinates'][0] ?? []);

        $outerRing = $rings[0] ?? [];

        if (!is_array($outerRing) || count($outerRing) < 4) {
            throw ValidationException::withMessages([
                'polygon_geojson' => 'Polygon wajib memiliki minimal 3 titik dan titik penutup. Titik batas dapat lebih dari 4 titik.',
            ]);
        }

        return $geometry;
    }

    private function simpanPolygonWajib(int $lahanId, array $geometry, string $tableName = 'lahan_sawah'): void
    {
        if (!Schema::hasColumn($tableName, 'polygon_area')) {
            throw new \RuntimeException('Kolom polygon_area belum tersedia.');
        }

        $geojson = json_encode($geometry, JSON_UNESCAPED_UNICODE);

        if (Schema::hasColumn($tableName, 'polygon_geojson')) {
            DB::statement(
                "UPDATE $tableName SET polygon_area = ST_GeomFromGeoJSON(?), polygon_geojson = ? WHERE id = ?",
                [$geojson, $geojson, $lahanId]
            );

            return;
        }

        DB::statement(
            "UPDATE $tableName SET polygon_area = ST_GeomFromGeoJSON(?) WHERE id = ?",
            [$geojson, $lahanId]
        );
    }

    private function baseLahanQuery()
    {
        $sipetani = DB::table('lahan_sawah')
            ->leftJoin('users as pemilik', 'lahan_sawah.pemilik_id', '=', 'pemilik.id')
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
            ->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id')
            ->select($this->selectLahanWithGeo('lahan_sawah'));

        $huma = DB::table('lahan_huma')
            ->leftJoin('users as pemilik', 'lahan_huma.pemilik_id', '=', 'pemilik.id')
            ->leftJoin('kecamatan', 'lahan_huma.kecamatan_id', '=', 'kecamatan.id')
            ->select($this->selectLahanWithGeo('lahan_huma'));

        return $sipetani->union($huma);
    }

    private function selectLahanWithGeo(string $table = 'lahan_sawah'): array
    {
        $prefix = $table === 'lahan_huma' ? 'H-' : 'S-';
        $select = [
            DB::raw("CONCAT('$prefix', $table.id) as id"),
            "$table.pemilik_id",
            "$table.pemilik_id as user_id",
            "$table.kecamatan_id",
            $table === 'lahan_sawah' ? "$table.kelurahan_id" : DB::raw("NULL as kelurahan_id"),
            $table === 'lahan_sawah' ? "$table.tipe_lahan_id" : DB::raw("NULL as tipe_lahan_id"),
            "$table.nama_lahan",
            $table === 'lahan_huma' ? "$table.nama_pemilik as pemilik_lahan" : 'pemilik.nama_lengkap as pemilik_lahan',
            "$table.luas_lahan_hektar",
            $table === 'lahan_sawah' ? "$table.hasil_panen_ton" : DB::raw("NULL as hasil_panen_ton"),
            $table === 'lahan_sawah' ? "$table.produktivitas_ton_ha" : DB::raw("NULL as produktivitas_ton_ha"),
            "$table.alamat_detail",
            "$table.koordinat_tengah",
            "$table.latitude",
            "$table.longitude",
            "$table.status_verifikasi",
            $table === 'lahan_huma' ? "$table.nama_pemilik as nama_petani" : 'pemilik.nama_lengkap as nama_petani',
            'pemilik.email as email_petani',
            'kecamatan.nama_kecamatan',
            $table === 'lahan_sawah' ? 'kelurahan.nama_kelurahan' : DB::raw("NULL as nama_kelurahan"),
            $table === 'lahan_sawah' ? 'tipe_lahan.nama_tipe' : DB::raw("NULL as nama_tipe"),
            $table === 'lahan_huma' ? "$table.district_name" : DB::raw("NULL as district_name"),
            $table === 'lahan_huma' ? "$table.tipe_tanah" : DB::raw("NULL as tipe_tanah"),
        ];

        if (Schema::hasColumn($table, 'tahun_lbs')) {
            $select[] = "$table.tahun_lbs";
        } else {
            $select[] = DB::raw("NULL as tahun_lbs");
        }

        if (Schema::hasColumn($table, 'created_at')) {
            $select[] = "$table.created_at";
        } else {
            $select[] = DB::raw("NULL as created_at");
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $select[] = "$table.updated_at";
        } else {
            $select[] = DB::raw("NULL as updated_at");
        }

        if (Schema::hasColumn($table, 'status_spasial')) {
            $select[] = "$table.status_spasial";
        } else {
            // Check dynamically if polygon_area is null
            $select[] = DB::raw("CASE WHEN $table.polygon_area IS NOT NULL THEN 'SUDAH_DIPETAKAN' ELSE 'BELUM_DIPETAKAN' END as status_spasial");
        }

        $select[] = Schema::hasColumn($table, 'polygon_area')
            ? DB::raw("ST_AsGeoJSON($table.polygon_area) as polygon_geojson")
            : DB::raw("NULL as polygon_geojson");

        return $select;
    }

    private function getDetailLahan($id): ?array
    {
        $rows = $this->baseLahanQuery()->get();
        // We filter manually because baseLahanQuery returns a union query
        // that we cannot easily ->where() on the aliased ID in some SQL dialects
        $row = $rows->firstWhere('id', $id);

        return $row ? $this->normalisasiLahan($row) : null;
    }

    private function normalisasiLahan($row): array
    {
        $data = (array) $row;
        $polygon = $data['polygon_geojson'] ?? null;
        $hasPolygon = !empty($polygon);

        $data['luas_lahan_hektar'] = (float) ($data['luas_lahan_hektar'] ?? 0);
        $data['hasil_panen_ton'] = (float) ($data['hasil_panen_ton'] ?? 0);
        $data['produktivitas_ton_ha'] = (float) ($data['produktivitas_ton_ha'] ?? 0);
        $data['latitude'] = $data['latitude'] !== null ? (float) $data['latitude'] : null;
        $data['longitude'] = $data['longitude'] !== null ? (float) $data['longitude'] : null;
        $data['polygon_geojson'] = $polygon;
        $data['geojson'] = $polygon;
        $data['status_spasial'] = $hasPolygon ? 'SUDAH_DIPETAKAN' : 'BELUM_DIPETAKAN';
        $data['is_wajib_dipetakan'] = ($data['status_verifikasi'] ?? null) === 'DITERIMA' && $data['status_spasial'] === 'BELUM_DIPETAKAN';

        return $data;
    }

    private function beriNomorUrut(array $rows): array
    {
        return collect($rows)
            ->values()
            ->map(function ($row, $index) {
                $row['nomor_urut'] = $index + 1;

                return $row;
            })
            ->all();
    }

    private function buildFeatureCollection(array $rows): array
    {
        $features = [];

        foreach ($rows as $row) {
            $geometry = null;

            if (!empty($row['polygon_geojson'])) {
                $geometry = json_decode($row['polygon_geojson'], true);
            }

            if (!$geometry && !empty($row['latitude']) && !empty($row['longitude'])) {
                $geometry = [
                    'type' => 'Point',
                    'coordinates' => [(float) $row['longitude'], (float) $row['latitude']],
                ];
            }

            if (!$geometry) {
                continue;
            }

            $properties = $row;
            unset($properties['polygon_geojson'], $properties['geojson']);

            $features[] = [
                'type' => 'Feature',
                'geometry' => $geometry,
                'properties' => $properties,
            ];
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    private function buildSummary(array $rows): array
    {
        $total = count($rows);
        $sudah = collect($rows)->where('status_spasial', 'SUDAH_DIPETAKAN')->count();
        $belum = collect($rows)->where('status_spasial', 'BELUM_DIPETAKAN')->count();

        return [
            'total' => $total,
            'sudah_dipetakan' => $sudah,
            'belum_dipetakan' => $belum,
            'persentase_lengkap' => $total > 0 ? round(($sudah / $total) * 100, 2) : 0,
        ];
    }

    private function ambilPetani(): array
    {
        if (!Schema::hasTable('users')) {
            return [];
        }

        $query = DB::table('users')->select('id', 'nama_lengkap', 'email', 'role_id');

        if (Schema::hasColumn('users', 'role_id')) {
            $query->whereIn('role_id', [1, 5]);
        }

        return $query->orderBy('role_id')->orderBy('nama_lengkap')->get()->toArray();
    }

    private function ambilTabel(string $table, array $columns, string $orderBy): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }

        $availableColumns = collect($columns)
            ->filter(fn ($column) => Schema::hasColumn($table, $column))
            ->values()
            ->all();

        if (empty($availableColumns)) {
            return [];
        }

        $query = DB::table($table)->select($availableColumns);

        if (Schema::hasColumn($table, $orderBy)) {
            $query->orderBy($orderBy);
        }

        return $query->get()->toArray();
    }

    private function lahanAda($id): bool
    {
        return DB::table('lahan_sawah')->where('id', $id)->exists();
    }

    private function filterExistingColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $key) => Schema::hasColumn($table, $key))
            ->toArray();
    }
}
