<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LahanSawah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class LahanSawahController extends Controller
{
    /**
     * Menampilkan data lahan milik petani yang sedang login.
     * Data PENDING tetap tampil di riwayat petani, tetapi belum legal.
     */
    public function index(Request $request)
    {
        $userId = $this->getAuthUserId($request);

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak terautentikasi'
            ], 401);
        }

        $query = LahanSawah::where('user_id', $userId)
            ->select(
                'id',
                'user_id',
                'kecamatan_id',
                'kelurahan_id',
                'tipe_lahan_id',
                'nama_lahan',
                'pemilik_lahan',
                'tahun_lbs',
                'luas_lahan_hektar',
                'hasil_panen_ton',
                'produktivitas_ton_ha',
                'alamat_detail',
                'koordinat_tengah',
                'latitude',
                'longitude',
                'foto_lahan',
                'status_verifikasi',
                'created_at'
            )
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status_verifikasi', strtoupper($request->status));
        }

        return response()->json([
            'success' => true,
            'message' => 'Data lahan berhasil diambil',
            'data' => $query->paginate($request->get('per_page', 5))
        ]);
    }

    /**
     * Dropdown lahan untuk input panen.
     * Hanya lahan DITERIMA yang boleh dipakai.
     */
    public function dropdown(Request $request)
    {
        $userId = $this->getAuthUserId($request);

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak terautentikasi'
            ], 401);
        }

        $data = LahanSawah::where('user_id', $userId)
            ->where('status_verifikasi', 'DITERIMA')
            ->select(
                'id',
                'nama_lahan',
                'luas_lahan_hektar',
                'alamat_detail'
            )
            ->orderBy('nama_lahan')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Dropdown lahan legal berhasil diambil',
            'data' => $data
        ]);
    }

    /**
     * Data lahan legal untuk petugas.
     * Dipakai pada monitoring dan daftar lahan yang sudah sah.
     */
    public function accepted(Request $request)
    {
        $query = DB::table('lahan_sawah')
            ->leftJoin('users', 'lahan_sawah.user_id', '=', 'users.id')
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
            ->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id')
            ->where('lahan_sawah.status_verifikasi', 'DITERIMA')
            ->select($this->selectLahanWithGeo());

        if ($request->filled('kecamatan_id')) {
            $query->where('lahan_sawah.kecamatan_id', $request->kecamatan_id);
        }

        if ($request->filled('kelurahan_id')) {
            $query->where('lahan_sawah.kelurahan_id', $request->kelurahan_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data lahan legal berhasil diambil',
            'data' => $query->orderBy('lahan_sawah.nama_lahan')->get()
        ]);
    }

    /**
     * Antrean lahan baru dari petani.
     * Dipakai pada dashboard/verifikasi role petugas.
     */
    public function pending(Request $request)
    {
        $query = DB::table('lahan_sawah')
            ->leftJoin('users', 'lahan_sawah.user_id', '=', 'users.id')
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
            ->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id')
            ->where('lahan_sawah.status_verifikasi', 'PENDING')
            ->select($this->selectLahanWithGeo());

        if ($request->filled('kecamatan_id')) {
            $query->where('lahan_sawah.kecamatan_id', $request->kecamatan_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Antrean pengajuan lahan berhasil diambil',
            'data' => $query->orderByDesc('lahan_sawah.id')->get()
        ]);
    }

    /**
     * Detail lahan.
     * Polygon dikembalikan sebagai GeoJSON agar tidak menyebabkan error UTF-8.
     */
    public function show($id)
    {
        $data = $this->getDetailLahan($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data lahan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail lahan sawah berhasil diambil',
            'data' => $data
        ]);
    }

    /**
     * Input lahan dari petani.
     * Jangan langsung legal, selalu masuk sebagai PENDING.
     */
    public function store(Request $request)
    {
        $userId = $this->getAuthUserId($request);

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak terautentikasi'
            ], 401);
        }

        $request->validate([
            'kecamatan_id' => 'required|integer',
            'kelurahan_id' => 'required|integer',
            'tipe_lahan_id' => 'nullable|integer',
            'nama_lahan' => 'required|string|max:100',
            'pemilik_lahan' => 'nullable|string|max:100',
            'tahun_lbs' => 'nullable|in:2017,2024',
            'luas_lahan_hektar' => 'nullable|numeric|min:0',
            'alamat_detail' => 'required|string|max:150',
            'koordinat_tengah' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'foto_lahan' => 'nullable|string|max:150',
            'polygon_wkt' => 'nullable|string',
            'polygon_geojson' => 'nullable|string',
        ]);

        $payload = [
            'user_id' => $userId,
            'kecamatan_id' => $request->kecamatan_id,
            'kelurahan_id' => $request->kelurahan_id,
            'tipe_lahan_id' => $request->tipe_lahan_id,
            'nama_lahan' => $request->nama_lahan,
            'pemilik_lahan' => $request->pemilik_lahan,
            'tahun_lbs' => $request->tahun_lbs ?? '2024',
            'luas_lahan_hektar' => $request->luas_lahan_hektar ?? 0,
            'hasil_panen_ton' => 0,
            'produktivitas_ton_ha' => 0,
            'alamat_detail' => $request->alamat_detail,
            'koordinat_tengah' => $request->koordinat_tengah,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'foto_lahan' => $request->foto_lahan,
            'status_verifikasi' => 'PENDING',
        ];

        $payload = $this->filterExistingColumns('lahan_sawah', $payload);

        $data = LahanSawah::create($payload);

        $this->simpanPolygonJikaAda($data->id, $request);

        $this->buatNotifikasiPetugas(
            'Pengajuan Lahan Baru',
            'Petani mengajukan lahan baru: ' . $data->nama_lahan . '. Segera lakukan verifikasi.'
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan lahan berhasil dikirim dan menunggu verifikasi petugas',
            'data' => $this->getDetailLahan($data->id)
        ], 201);
    }

    /**
     * Petugas menerima lahan.
     * Setelah diterima, lahan menjadi legal dan boleh muncul di frontend/peta/statistik.
     */
    public function approve(Request $request, $id)
    {
        $data = LahanSawah::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data lahan tidak ditemukan'
            ], 404);
        }

        if ($data->status_verifikasi === 'DITERIMA') {
            return response()->json([
                'success' => false,
                'message' => 'Lahan sudah diterima sebelumnya'
            ], 400);
        }

        $request->validate([
            'kecamatan_id' => 'nullable|integer',
            'kelurahan_id' => 'nullable|integer',
            'tipe_lahan_id' => 'nullable|integer',
            'nama_lahan' => 'nullable|string|max:100',
            'pemilik_lahan' => 'nullable|string|max:100',
            'tahun_lbs' => 'nullable|in:2017,2024',
            'luas_lahan_hektar' => 'nullable|numeric|min:0',
            'alamat_detail' => 'nullable|string|max:150',
            'koordinat_tengah' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'foto_lahan' => 'nullable|string|max:150',
            'polygon_wkt' => 'nullable|string',
            'polygon_geojson' => 'nullable|string',
        ]);

        $payload = [
            'status_verifikasi' => 'DITERIMA',
        ];

        $fieldOpsional = [
            'kecamatan_id',
            'kelurahan_id',
            'tipe_lahan_id',
            'nama_lahan',
            'pemilik_lahan',
            'tahun_lbs',
            'luas_lahan_hektar',
            'alamat_detail',
            'koordinat_tengah',
            'latitude',
            'longitude',
            'foto_lahan',
        ];

        foreach ($fieldOpsional as $field) {
            if ($request->filled($field)) {
                $payload[$field] = $request->input($field);
            }
        }

        $payload = $this->filterExistingColumns('lahan_sawah', $payload);

        $data->update($payload);

        $this->simpanPolygonJikaAda($data->id, $request);
        $this->hitungUlangProduktivitasLahan($data->id);

        return response()->json([
            'success' => true,
            'message' => 'Lahan berhasil diterima dan menjadi data legal',
            'data' => $this->getDetailLahan($data->id)
        ]);
    }

    /**
     * Petugas menolak lahan.
     * Data tetap tersimpan sebagai riwayat, tetapi tidak legal dan tidak masuk frontend resmi.
     */
    public function reject(Request $request, $id)
    {
        $data = LahanSawah::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data lahan tidak ditemukan'
            ], 404);
        }

        if ($data->status_verifikasi === 'DITOLAK') {
            return response()->json([
                'success' => false,
                'message' => 'Lahan sudah ditolak sebelumnya'
            ], 400);
        }

        $data->update([
            'status_verifikasi' => 'DITOLAK'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lahan berhasil ditolak',
            'data' => $this->getDetailLahan($data->id)
        ]);
    }

    /**
     * Ambil user id dari JWT middleware.
     */
    private function getAuthUserId(Request $request): ?int
    {
        $user = $request->attributes->get('auth');

        if (!$user) {
            return null;
        }

        return $user->sub ?? $user->id ?? null;
    }

    /**
     * Select eksplisit agar polygon_area tidak dikirim sebagai binary mentah.
     */
    private function selectLahanWithGeo(): array
    {
        return [
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
            'lahan_sawah.koordinat_tengah',
            'lahan_sawah.latitude',
            'lahan_sawah.longitude',
            'lahan_sawah.foto_lahan',
            'lahan_sawah.status_verifikasi',
            'lahan_sawah.created_at',
            'users.nama_lengkap as nama_petani',
            'users.email as email_petani',
            'kecamatan.nama_kecamatan',
            'kelurahan.nama_kelurahan',
            'tipe_lahan.nama_tipe',
            DB::raw('ST_AsGeoJSON(lahan_sawah.polygon_area) as polygon_geojson'),
        ];
    }

    /**
     * Detail lahan dengan join data pendukung.
     */
    private function getDetailLahan($id)
    {
        return DB::table('lahan_sawah')
            ->leftJoin('users', 'lahan_sawah.user_id', '=', 'users.id')
            ->leftJoin('kecamatan', 'lahan_sawah.kecamatan_id', '=', 'kecamatan.id')
            ->leftJoin('kelurahan', 'lahan_sawah.kelurahan_id', '=', 'kelurahan.id')
            ->leftJoin('tipe_lahan', 'lahan_sawah.tipe_lahan_id', '=', 'tipe_lahan.id')
            ->where('lahan_sawah.id', $id)
            ->select($this->selectLahanWithGeo())
            ->first();
    }

    /**
     * Simpan polygon jika dikirim oleh petugas.
     * Mendukung polygon_wkt dan polygon_geojson.
     */
    private function simpanPolygonJikaAda(int $lahanId, Request $request): void
    {
        try {
            if ($request->filled('polygon_wkt')) {
                DB::statement(
                    'UPDATE lahan_sawah SET polygon_area = ST_GeomFromText(?, 4326) WHERE id = ?',
                    [$request->polygon_wkt, $lahanId]
                );
            }

            if ($request->filled('polygon_geojson')) {
                DB::statement(
                    'UPDATE lahan_sawah SET polygon_area = ST_GeomFromGeoJSON(?) WHERE id = ?',
                    [$request->polygon_geojson, $lahanId]
                );
            }
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan polygon lahan: ' . $e->getMessage());
        }
    }

    /**
     * Hitung ulang total panen dan produktivitas lahan.
     * Hanya panen berstatus DITERIMA yang dihitung.
     */
    private function hitungUlangProduktivitasLahan(int $lahanId): void
    {
        $lahan = LahanSawah::find($lahanId);

        if (!$lahan) {
            return;
        }

        $totalPanen = DB::table('siklus_tanam')
            ->where('lahan_id', $lahanId)
            ->where('status_verifikasi', 'DITERIMA')
            ->sum(DB::raw('COALESCE(hasil_panen, 0)'));

        $luas = (float) $lahan->luas_lahan_hektar;
        $produktivitas = $luas > 0 ? $totalPanen / $luas : 0;

        $payload = [
            'hasil_panen_ton' => round($totalPanen, 2),
            'produktivitas_ton_ha' => round($produktivitas, 2),
        ];

        $payload = $this->filterExistingColumns('lahan_sawah', $payload);

        $lahan->update($payload);
    }

    /**
     * Buat notifikasi untuk semua petugas.
     */
    private function buatNotifikasiPetugas(string $judul, string $pesan): void
    {
        try {
            if (!Schema::hasTable('notifikasi')) {
                return;
            }

            DB::table('notifikasi')->insert([
                'role_id_penerima' => 2,
                'user_id_penerima' => null,
                'judul' => $judul,
                'pesan' => $pesan,
                'is_read' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat notifikasi petugas: ' . $e->getMessage());
        }
    }

    /**
     * Menghindari error jika ada field yang tidak ada di tabel database.
     */
    private function filterExistingColumns(string $table, array $payload): array
    {
        return collect($payload)
            ->filter(function ($value, $key) use ($table) {
                return Schema::hasColumn($table, $key);
            })
            ->toArray();
    }
}