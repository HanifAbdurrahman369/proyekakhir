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
        $query = $this->baseLahanQuery();

        $status = strtoupper((string) $request->query('status', 'DITERIMA'));
        if ($status !== 'ALL') {
            $query->where('lahan_sawah.status_verifikasi', $status);
        }

        if ($request->filled('kecamatan_id')) {
            $query->where('lahan_sawah.kecamatan_id', $request->query('kecamatan_id'));
        }

        if ($request->filled('kelurahan_id')) {
            $query->where('lahan_sawah.kelurahan_id', $request->query('kelurahan_id'));
        }

        $rows = $query
            ->orderByRaw("CASE WHEN lahan_sawah.status_verifikasi = 'DITERIMA' THEN 0 ELSE 1 END")
            ->orderBy('lahan_sawah.nama_lahan')
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
        [$payload, $geometry] = $this->validasiPayloadSpasial($request, true);

        $payload['status_verifikasi'] = 'DITERIMA';
        $payload['status_spasial'] = 'SUDAH_DIPETAKAN';

        if (Schema::hasColumn('lahan_sawah', 'created_at')) {
            $payload['created_at'] = now();
        }

        if (Schema::hasColumn('lahan_sawah', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        try {
            $id = DB::transaction(function () use ($payload, $geometry) {
                $id = DB::table('lahan_sawah')->insertGetId(
                    $this->filterExistingColumns('lahan_sawah', $payload)
                );

                $this->simpanPolygonWajib($id, $geometry);

                return $id;
            });

            return response()->json([
                'success' => true,
                'message' => 'Data spasial lahan berhasil dibuat.',
                'data' => $this->getDetailLahan($id),
            ], 201, [], JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat data spasial lahan: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat data spasial lahan. Pastikan polygon valid dan kolom spasial tersedia.',
            ], 422);
        }
    }

    public function update(Request $request, $id)
    {
        $existing = DB::table('lahan_sawah')
            ->where('id', $id)
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

        if (Schema::hasColumn('lahan_sawah', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        try {
            DB::transaction(function () use ($id, $payload, $geometry) {
                DB::table('lahan_sawah')
                    ->where('id', $id)
                    ->update($this->filterExistingColumns('lahan_sawah', $payload));

                $this->simpanPolygonWajib((int) $id, $geometry);
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
        if (!$this->lahanAda($id)) {
            return response()->json([
                'success' => false,
                'message' => 'Data lahan tidak ditemukan.',
            ], 404);
        }

        try {
            DB::transaction(function () use ($id) {
                if (Schema::hasColumn('lahan_sawah', 'polygon_area')) {
                    DB::statement('UPDATE lahan_sawah SET polygon_area = NULL WHERE id = ?', [$id]);
                }

                $payload = [
                    'latitude' => null,
                    'longitude' => null,
                    'koordinat_tengah' => null,
                    'status_spasial' => 'BELUM_DIPETAKAN',
                ];

                if (Schema::hasColumn('lahan_sawah', 'polygon_geojson')) {
                    $payload['polygon_geojson'] = null;
                }

                if (Schema::hasColumn('lahan_sawah', 'spasial_updated_at')) {
                    $payload['spasial_updated_at'] = now();
                }

                if (Schema::hasColumn('lahan_sawah', 'updated_at')) {
                    $payload['updated_at'] = now();
                }

                DB::table('lahan_sawah')
                    ->where('id', $id)
                    ->update($this->filterExistingColumns('lahan_sawah', $payload));
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
            'kecamatan_id' => 'required|integer',
            'kelurahan_id' => 'nullable|integer',
            'tipe_lahan_id' => 'nullable|integer',
            'nama_lahan' => 'required|string|max:100',
            'pemilik_lahan' => 'nullable|string|max:100',
            'tahun_lbs' => 'nullable|in:2017,2024',
            'luas_lahan_hektar' => 'required|numeric|min:0.0001',
            'alamat_detail' => 'nullable|string|max:500',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'foto_lahan' => 'nullable|string|max:150',
            'polygon_geojson' => 'required|string',
            'spasial_updated_by' => 'nullable|integer',
        ]);

        $geometry = $this->validasiGeoJson($request->input('polygon_geojson'));
        $normalizedGeoJson = json_encode($geometry, JSON_UNESCAPED_UNICODE);

        $lat = (float) $request->input('latitude');
        $lng = (float) $request->input('longitude');

        $payload = [
            'user_id' => $request->filled('user_id') ? $request->input('user_id') : null,
            'kecamatan_id' => $request->input('kecamatan_id'),
            'kelurahan_id' => $request->input('kelurahan_id'),
            'tipe_lahan_id' => $request->input('tipe_lahan_id'),
            'nama_lahan' => $request->input('nama_lahan'),
            'pemilik_lahan' => $request->input('pemilik_lahan'),
            'tahun_lbs' => $request->input('tahun_lbs', '2024'),
            'luas_lahan_hektar' => $request->input('luas_lahan_hektar'),
            'alamat_detail' => $request->input('alamat_detail'),
            'koordinat_tengah' => $lat . ',' . $lng,
            'latitude' => $lat,
            'longitude' => $lng,
            'foto_lahan' => $request->input('foto_lahan'),
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

        if (!$isCreate && !$request->filled('user_id')) {
            unset($payload['user_id']);
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

    private function simpanPolygonWajib(int $lahanId, array $geometry): void
    {
        if (!Schema::hasColumn('lahan_sawah', 'polygon_area')) {
            throw new \RuntimeException('Kolom polygon_area belum tersedia.');
        }

        $geojson = json_encode($geometry, JSON_UNESCAPED_UNICODE);

        if (Schema::hasColumn('lahan_sawah', 'polygon_geojson')) {
            DB::statement(
                'UPDATE lahan_sawah SET polygon_area = ST_GeomFromGeoJSON(?), polygon_geojson = ? WHERE id = ?',
                [$geojson, $geojson, $lahanId]
            );

            return;
        }

        DB::statement(
            'UPDATE lahan_sawah SET polygon_area = ST_GeomFromGeoJSON(?) WHERE id = ?',
            [$geojson, $lahanId]
        );
    }

    private function baseLahanQuery()
    {
        return DB::table('lahan_sawah')
            ->leftJoin('users', 'lahan_sawah.user_id', '=', 'users.id')
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
            ->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id')
            ->select($this->selectLahanWithGeo());
    }

    private function selectLahanWithGeo(): array
    {
        $select = [
            'lahan_sawah.id',
            'lahan_sawah.user_id',
            'lahan_sawah.kecamatan_id',
            'lahan_sawah.kelurahan_id',
            'lahan_sawah.tipe_lahan_id',
            'lahan_sawah.nama_lahan',
            'lahan_sawah.pemilik_lahan',
            'lahan_sawah.luas_lahan_hektar',
            'lahan_sawah.hasil_panen_ton',
            'lahan_sawah.produktivitas_ton_ha',
            'lahan_sawah.alamat_detail',
            'lahan_sawah.koordinat_tengah',
            'lahan_sawah.latitude',
            'lahan_sawah.longitude',
            'lahan_sawah.foto_lahan',
            'lahan_sawah.status_verifikasi',
            'users.nama_lengkap as nama_petani',
            'users.email as email_petani',
            'kecamatan.nama_kecamatan',
            'kelurahan.nama_kelurahan',
            'tipe_lahan.nama_tipe',
        ];

        if (Schema::hasColumn('lahan_sawah', 'tahun_lbs')) {
            $select[] = 'lahan_sawah.tahun_lbs';
        }

        if (Schema::hasColumn('lahan_sawah', 'created_at')) {
            $select[] = 'lahan_sawah.created_at';
        }

        if (Schema::hasColumn('lahan_sawah', 'updated_at')) {
            $select[] = 'lahan_sawah.updated_at';
        }

        if (Schema::hasColumn('lahan_sawah', 'status_spasial')) {
            $select[] = 'lahan_sawah.status_spasial';
        }

        $select[] = Schema::hasColumn('lahan_sawah', 'polygon_area')
            ? DB::raw('ST_AsGeoJSON(lahan_sawah.polygon_area) as polygon_geojson')
            : DB::raw('NULL as polygon_geojson');

        return $select;
    }

    private function getDetailLahan($id): ?array
    {
        $row = $this->baseLahanQuery()
            ->where('lahan_sawah.id', $id)
            ->first();

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

        $query = DB::table('users')->select('id', 'nama_lengkap', 'email');

        if (Schema::hasColumn('users', 'role_id')) {
            $query->where('role_id', 1);
        }

        return $query->orderBy('nama_lengkap')->get()->toArray();
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
