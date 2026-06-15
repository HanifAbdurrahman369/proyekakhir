<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SiklusTanam;
use App\Models\LahanSawah;
use App\Models\LaporPanen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SiklusTanamController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => SiklusTanam::with(['bibit', 'lahan'])->orderByDesc('id')->get()
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function store(Request $request)
    {
        $request->validate([
            'lahan_id' => 'required|integer',
            'bibit_id' => 'required|integer',
            'tanggal_tanam' => 'required|date',
            'estimasi_panen' => 'nullable',
            'tanggal_panen' => 'required|date',
            'hasil_panen' => 'required|numeric|min:0',
        ]);

        $user = $request->attributes->get('auth');
        $userId = $user->sub ?? $user->id ?? null;

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Token pengguna tidak valid.'
            ], 401);
        }

        $lahan = LahanSawah::where('id', $request->lahan_id)
            ->where('user_id', $userId)
            ->where('status_verifikasi', 'DITERIMA')
            ->first();

        if (!$lahan) {
            return response()->json([
                'success' => false,
                'message' => 'Lahan tidak valid atau belum diverifikasi petugas.'
            ], 403);
        }

        $data = SiklusTanam::create([
            'lahan_id' => $request->lahan_id,
            'bibit_id' => $request->bibit_id,
            'tanggal_tanam' => $request->tanggal_tanam,
            'estimasi_panen' => $request->estimasi_panen,
            'tanggal_panen' => $request->tanggal_panen,
            'hasil_panen' => $request->hasil_panen,
            'status_aktif' => 'AKTIF',
            'status_verifikasi' => 'PENDING',
            'created_by' => $userId,
        ]);

        $petani = DB::table('users')->where('id', $userId)->first();
        $bibit = DB::table('jenis_bibit')->where('id', $request->bibit_id)->first();

        $this->buatNotifikasiPetugas(
            'Laporan Hasil Panen Baru',
            'Petani ' . ($petani->nama_lengkap ?? '-') . ' mengirim laporan panen untuk lahan ' . ($lahan->nama_lahan ?? '-') . ' dengan bibit ' . ($bibit->nama_bibit ?? '-') . '. Segera lakukan verifikasi.',
            'siklus_tanam',
            (int) $data->id,
            '/verifikasi-data-petani?tipe=panen&id=' . $data->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Laporan hasil panen berhasil dikirim dan menunggu verifikasi petugas',
            'data' => $this->ambilDetailHasilPanen((int) $data->id)
        ], 201, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function storeLaporPanen(Request $request)
    {
        $request->validate([
            'siklus_tanam_id' => 'required|integer',
            'tanggal_panen' => 'required|date',
            'hasil_panen' => 'required|numeric|min:0',
            'estimasi_panen' => 'nullable|integer',
        ]);

        $user = $request->attributes->get('auth');
        $userId = $user->sub ?? $user->id ?? null;

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Token pengguna tidak valid.'
            ], 401);
        }

        $siklus = SiklusTanam::where('id', $request->siklus_tanam_id)
            ->where('created_by', $userId)
            ->first();

        if (!$siklus) {
            return response()->json([
                'success' => false,
                'message' => 'Siklus tanam tidak ditemukan atau bukan milik Anda.'
            ], 403);
        }

        $data = LaporPanen::create([
            'siklus_tanam_id' => $request->siklus_tanam_id,
            'tanggal_panen' => $request->tanggal_panen,
            'hasil_panen' => $request->hasil_panen,
            'estimasi_panen' => $request->estimasi_panen,
            'status_verifikasi' => 'PENDING',
            'created_by' => $userId,
        ]);

        $petani = DB::table('users')->where('id', $userId)->first();
        $lahan = LahanSawah::find($siklus->lahan_id);

        $this->buatNotifikasiPetugas(
            'Laporan Panen Baru',
            'Petani ' . ($petani->nama_lengkap ?? '-') . ' mengirim laporan panen untuk lahan ' . ($lahan->nama_lahan ?? '-') . '. Segera lakukan verifikasi.',
            'lapor_panen',
            (int) $data->id,
            '/verifikasi-data-petani?tipe=panen&id=' . $data->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Laporan panen berhasil dikirim dan menunggu verifikasi petugas',
            'data' => $data
        ], 201);
    }

    public function show($id)
    {
        $data = $this->ambilDetailHasilPanen((int) $id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function update(Request $request, $id)
    {
        $data = SiklusTanam::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        if ($data->status_verifikasi === 'DITERIMA') {
            return response()->json([
                'success' => false,
                'message' => 'Data yang sudah diverifikasi tidak boleh diubah'
            ], 400);
        }

        $request->validate([
            'lahan_id' => 'required|integer',
            'bibit_id' => 'required|integer',
            'tanggal_tanam' => 'required|date',
            'estimasi_panen' => 'nullable',
            'tanggal_panen' => 'required|date',
            'hasil_panen' => 'required|numeric|min:0',
        ]);

        $data->update([
            'lahan_id' => $request->lahan_id,
            'bibit_id' => $request->bibit_id,
            'tanggal_tanam' => $request->tanggal_tanam,
            'estimasi_panen' => $request->estimasi_panen,
            'tanggal_panen' => $request->tanggal_panen,
            'hasil_panen' => $request->hasil_panen,
            'status_verifikasi' => 'PENDING',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Aktivitas tanam berhasil diperbarui dan kembali menunggu verifikasi petugas',
            'data' => $this->ambilDetailHasilPanen((int) $data->id)
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function totalProduksi(Request $request)
    {
        $user = $request->attributes->get('auth');
        $userId = $user->sub ?? $user->id ?? null;
        $tahun = Carbon::now()->year;

        $total = SiklusTanam::where('created_by', $userId)
            ->where('status_verifikasi', 'DITERIMA')
            ->whereYear('tanggal_panen', $tahun)
            ->sum('hasil_panen');

        return response()->json([
            'success' => true,
            'data' => [
                'tahun' => $tahun,
                'total_produksi' => (float) $total
            ]
        ]);
    }

    public function getPendingVerifications()
    {
        $data = $this->queryHasilPanenAman()
            ->where('lp.status_verifikasi', 'PENDING')
            ->orderByDesc('lp.created_at')
            ->orderByDesc('lp.id')
            ->get()
            ->map(fn ($row) => $this->formatHasilPanen($row))
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Antrean verifikasi hasil panen berhasil diambil.',
            'data' => $data,
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function verifyHarvest(Request $request, $id)
    {
        $aksi = strtoupper((string) $request->input('aksi', $request->input('status', '')));

        if (in_array($aksi, ['DITERIMA', 'TERIMA', 'SETUJU', 'APPROVE', 'APPROVED'], true)) {
            return $this->approve($id);
        }

        if (in_array($aksi, ['DITOLAK', 'TOLAK', 'REJECT', 'REJECTED'], true)) {
            return $this->reject($id);
        }

        return response()->json([
            'success' => false,
            'message' => 'Aksi verifikasi tidak valid. Gunakan DITERIMA atau DITOLAK.',
        ], 422);
    }

    public function approve($id)
    {
        $result = DB::transaction(function () use ($id) {
            $data = LaporPanen::where('id', $id)->lockForUpdate()->first();

            if (!$data) {
                return [
                    'status' => 404,
                    'body' => [
                        'success' => false,
                        'message' => 'Data hasil panen tidak ditemukan',
                    ],
                ];
            }

            $data->update([
                'status_verifikasi' => 'DITERIMA',
            ]);

            $this->tandaiNotifikasiPanenTerbaca((int) $data->id, 'lapor_panen');

            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'message' => 'Laporan panen berhasil disetujui.',
                    'data' => $this->ambilDetailHasilPanen((int) $data->id),
                ],
            ];
        });

        return response()->json($result['body'], $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function reject($id)
    {
        $data = LaporPanen::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data hasil panen tidak ditemukan'
            ], 404);
        }

        if ($data->status_verifikasi === 'DITERIMA') {
            return response()->json([
                'success' => false,
                'message' => 'Data hasil panen yang sudah diterima tidak boleh ditolak ulang.'
            ], 400);
        }

        $data->update([
            'status_verifikasi' => 'DITOLAK',
            'catatan_verifikasi' => request()->input('alasan_penolakan') ?? request()->input('catatan_verifikasi')
        ]);

        $this->tandaiNotifikasiPanenTerbaca((int) $data->id, 'lapor_panen');

        return response()->json([
            'success' => true,
            'message' => 'Laporan panen berhasil ditolak',
            'data' => $this->ambilDetailHasilPanen((int) $data->id),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function destroy($id)
    {
        $data = SiklusTanam::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        if ($data->status_verifikasi === 'DITERIMA') {
            return response()->json([
                'success' => false,
                'message' => 'Data yang sudah diverifikasi tidak boleh dihapus'
            ], 400);
        }

        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }

    private function queryHasilPanenAman()
    {
        return DB::table('lapor_panen as lp')
            ->join('siklus_tanam as st', 'st.id', '=', 'lp.siklus_tanam_id')
            ->join('lahan_sawah as ls', 'ls.id', '=', 'st.lahan_id')
            ->leftJoin('users as u', 'u.id', '=', 'st.created_by')
            ->leftJoin('jenis_bibit as jb', 'jb.id', '=', 'st.bibit_id')
            ->leftJoin('kecamatan as kc', 'kc.id', '=', 'ls.kecamatan_id')
            ->leftJoin('kelurahan as kl', 'kl.id', '=', 'ls.kelurahan_id')
            ->select([
                'lp.id',
                'lp.siklus_tanam_id',
                'st.lahan_id',
                'st.bibit_id',
                'st.tanggal_tanam',
                'lp.estimasi_panen',
                'st.status_aktif',
                'lp.tanggal_panen',
                'lp.hasil_panen',
                'lp.status_verifikasi',
                'lp.catatan_verifikasi',
                'lp.created_by',
                'lp.created_at',
                'lp.updated_at',
                'ls.nama_lahan',
                'ls.pemilik_lahan',
                'ls.luas_lahan_hektar',
                'ls.hasil_panen_ton as lahan_hasil_panen_ton',
                'ls.produktivitas_ton_ha as lahan_produktivitas_ton_ha',
                'ls.alamat_detail',
                'ls.latitude',
                'ls.longitude',
                'u.nama_lengkap as nama_petani',
                'u.email as email_petani',
                'u.no_hp as no_hp_petani',
                'jb.nama_bibit',
                'jb.varietas',
                'jb.masa_tanam_hari',
                'kc.nama_kecamatan',
                'kl.nama_kelurahan',
            ]);
    }

    private function ambilDetailHasilPanen(int $id): ?array
    {
        $row = $this->queryHasilPanenAman()->where('lp.id', $id)->first();

        return $row ? $this->formatHasilPanen($row) : null;
    }

    private function formatHasilPanen($row): array
    {
        $hasilPanen = $row->hasil_panen !== null ? (float) $row->hasil_panen : null;
        $luasLahan = $row->luas_lahan_hektar !== null ? (float) $row->luas_lahan_hektar : 0;
        $produktivitasPengajuan = ($hasilPanen !== null && $luasLahan > 0)
            ? round($hasilPanen / $luasLahan, 2)
            : 0;

        return [
            'id' => (int) $row->id,
            'siklus_tanam_id' => (int) ($row->siklus_tanam_id ?? 0),
            'lahan_id' => (int) $row->lahan_id,
            'bibit_id' => (int) $row->bibit_id,
            'tanggal_tanam' => $row->tanggal_tanam,
            'estimasi_panen' => $row->estimasi_panen,
            'status_aktif' => $row->status_aktif,
            'tanggal_panen' => $row->tanggal_panen,
            'hasil_panen' => $hasilPanen,
            'hasil_panen_label' => number_format((float) ($hasilPanen ?? 0), 2, ',', '.') . ' Ton',
            'status_verifikasi' => $row->status_verifikasi,
            'catatan_verifikasi' => $this->safeText($row->catatan_verifikasi),
            'created_by' => (int) $row->created_by,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'nama_petani' => $this->safeText($row->nama_petani),
            'email_petani' => $this->safeText($row->email_petani),
            'no_hp_petani' => $this->safeText($row->no_hp_petani),
            'nama_bibit' => $this->safeText($row->nama_bibit),
            'varietas' => $this->safeText($row->varietas),
            'masa_tanam_hari' => $row->masa_tanam_hari !== null ? (int) $row->masa_tanam_hari : null,
            'nama_lahan' => $this->safeText($row->nama_lahan),
            'pemilik_lahan' => $this->safeText($row->pemilik_lahan),
            'luas_lahan_hektar' => $luasLahan,
            'produktivitas_pengajuan_ton_ha' => $produktivitasPengajuan,
            'lahan_hasil_panen_ton' => $row->lahan_hasil_panen_ton !== null ? (float) $row->lahan_hasil_panen_ton : null,
            'lahan_produktivitas_ton_ha' => $row->lahan_produktivitas_ton_ha !== null ? (float) $row->lahan_produktivitas_ton_ha : null,
            'alamat_detail' => $this->safeText($row->alamat_detail),
            'latitude' => $row->latitude !== null ? (float) $row->latitude : null,
            'longitude' => $row->longitude !== null ? (float) $row->longitude : null,
            'nama_kecamatan' => $this->safeText($row->nama_kecamatan),
            'nama_kelurahan' => $this->safeText($row->nama_kelurahan),
            'petani' => [
                'id' => (int) $row->created_by,
                'nama_lengkap' => $this->safeText($row->nama_petani),
                'email' => $this->safeText($row->email_petani),
                'no_hp' => $this->safeText($row->no_hp_petani),
            ],
            'bibit' => [
                'id' => (int) $row->bibit_id,
                'nama_bibit' => $this->safeText($row->nama_bibit),
                'varietas' => $this->safeText($row->varietas),
                'masa_tanam_hari' => $row->masa_tanam_hari !== null ? (int) $row->masa_tanam_hari : null,
            ],
            'lahan' => [
                'id' => (int) $row->lahan_id,
                'nama_lahan' => $this->safeText($row->nama_lahan),
                'pemilik_lahan' => $this->safeText($row->pemilik_lahan),
                'luas_lahan_hektar' => $luasLahan,
                'alamat_detail' => $this->safeText($row->alamat_detail),
                'latitude' => $row->latitude !== null ? (float) $row->latitude : null,
                'longitude' => $row->longitude !== null ? (float) $row->longitude : null,
                'nama_kecamatan' => $this->safeText($row->nama_kecamatan),
                'nama_kelurahan' => $this->safeText($row->nama_kelurahan),
            ],
        ];
    }

    private function sinkronkanInfoLahanDariPanenTerakhir(int $lahanId): void
    {
        $lahan = LahanSawah::find($lahanId);

        if (!$lahan) {
            return;
        }

        $panenTerakhir = SiklusTanam::where('lahan_id', $lahanId)
            ->where('status_verifikasi', 'DITERIMA')
            ->whereNotNull('hasil_panen')
            ->orderByDesc('tanggal_panen')
            ->orderByDesc('id')
            ->first();

        if (!$panenTerakhir) {
            return;
        }

        $hasilPanen = (float) $panenTerakhir->hasil_panen;
        $luas = (float) ($lahan->luas_lahan_hektar ?? 0);
        $produktivitas = $luas > 0 ? round($hasilPanen / $luas, 2) : 0;

        $payload = [
            'hasil_panen_ton' => round($hasilPanen, 2),
            'produktivitas_ton_ha' => $produktivitas,
        ];

        if ($this->kolomAda('lahan_sawah', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('lahan_sawah')->where('id', $lahanId)->update($payload);
    }

    private function buatNotifikasiPetugas(string $judul, string $pesan, ?string $refType = null, ?int $refId = null, ?string $targetUrl = null): void
    {
        try {
            $payload = [
                'role_id_penerima' => 2,
                'user_id_penerima' => null,
                'judul' => $judul,
                'pesan' => $pesan,
                'is_read' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($this->kolomAda('notifikasi', 'ref_type')) {
                $payload['ref_type'] = $refType;
            }

            if ($this->kolomAda('notifikasi', 'ref_id')) {
                $payload['ref_id'] = $refId;
            }

            if ($this->kolomAda('notifikasi', 'target_url')) {
                $payload['target_url'] = $targetUrl ?: '/verifikasi-data-petani';
            }

            DB::table('notifikasi')->insert($payload);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat notifikasi petugas: ' . $e->getMessage());
        }
    }

    private function tandaiNotifikasiPanenTerbaca(int $id, string $refType = 'siklus_tanam'): void
    {
        try {
            if ($this->kolomAda('notifikasi', 'ref_type') && $this->kolomAda('notifikasi', 'ref_id')) {
                DB::table('notifikasi')
                    ->where('ref_type', $refType)
                    ->where('ref_id', $id)
                    ->update([
                        'is_read' => 1,
                        'updated_at' => now(),
                    ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal menandai notifikasi panen terbaca: ' . $e->getMessage());
        }
    }

    private function kolomAda(string $table, string $column): bool
    {
        try {
            return DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getJenisPupuk()
    {
        $data = DB::table('jenis_pupuk')->get()->map(function ($row) {
            return [
                'id' => (int) $row->id,
                'nama_pupuk' => $row->nama_bibit, // Note: mapped to nama_bibit in the schema
                'tipe' => $row->varietas,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getMySiklusTanam(Request $request)
    {
        $user = $request->attributes->get('auth');
        $userId = $user->sub ?? $user->id ?? null;

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Token pengguna tidak valid.'
            ], 401);
        }

        $data = SiklusTanam::with(['lahan', 'bibit'])
            ->where('created_by', $userId)
            ->orderByDesc('id')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'lahan_id' => $item->lahan_id,
                    'bibit_id' => $item->bibit_id,
                    'tanggal_tanam' => $item->tanggal_tanam,
                    'estimasi_panen' => $item->estimasi_panen,
                    'tanggal_panen' => $item->tanggal_panen,
                    'hasil_panen' => $item->hasil_panen,
                    'nama_lahan' => $item->lahan->nama_lahan ?? '-',
                    'nama_bibit' => $item->bibit->nama_bibit ?? '-',
                    'status_verifikasi' => $item->status_verifikasi,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function storeSiklusPupuk(Request $request)
    {
        $request->validate([
            'siklus_tanam_id' => 'required|integer',
            'pupuk_id' => 'required|integer',
            'tanggal_pemupukan' => 'required|date',
            'takaran' => 'required|numeric|min:0.01',
        ]);

        $user = $request->attributes->get('auth');
        $userId = $user->sub ?? $user->id ?? null;

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Token pengguna tidak valid.'
            ], 401);
        }

        // Verify that the siklus_tanam belongs to the user
        $siklus = SiklusTanam::where('id', $request->siklus_tanam_id)
            ->where('created_by', $userId)
            ->first();

        if (!$siklus) {
            return response()->json([
                'success' => false,
                'message' => 'Siklus tanam tidak valid.'
            ], 403);
        }

        $id = DB::table('siklus_pupuk')->insertGetId([
            'siklus_tanam_id' => $request->siklus_tanam_id,
            'pupuk_id' => $request->pupuk_id,
            'tanggal_pemupukan' => $request->tanggal_pemupukan,
            'takaran' => $request->takaran,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Catatan pemupukan berhasil disimpan.',
            'data' => [
                'id' => $id,
                'siklus_tanam_id' => $request->siklus_tanam_id,
                'pupuk_id' => $request->pupuk_id,
                'tanggal_pemupukan' => $request->tanggal_pemupukan,
                'takaran' => $request->takaran,
            ]
        ], 201);
    }

    public function getSiklusPupuk(Request $request)
    {
        $user = $request->attributes->get('auth');
        $userId = $user->sub ?? $user->id ?? null;

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Token pengguna tidak valid.'
            ], 401);
        }

        $perPage = $request->get('per_page', 3);
        $paginator = DB::table('siklus_pupuk as sp')
            ->join('siklus_tanam as st', 'sp.siklus_tanam_id', '=', 'st.id')
            ->join('lahan_sawah as ls', 'st.lahan_id', '=', 'ls.id')
            ->join('jenis_pupuk as jp', 'sp.pupuk_id', '=', 'jp.id')
            ->where('st.created_by', $userId)
            ->select([
                'sp.id',
                'ls.nama_lahan',
                'jp.nama_bibit as nama_pupuk',
                'jp.varietas as tipe_pupuk',
                'sp.tanggal_pemupukan',
                'sp.takaran'
            ])
            ->orderByDesc('sp.tanggal_pemupukan')
            ->orderByDesc('sp.id')
            ->paginate($perPage, ['*'], 'pupuk_page');

        $paginator->getCollection()->transform(function ($row) {
            return [
                'id' => (int) $row->id,
                'nama_lahan' => $this->safeText($row->nama_lahan),
                'nama_pupuk' => $this->safeText($row->nama_pupuk),
                'tipe_pupuk' => $this->safeText($row->tipe_pupuk),
                'tanggal_pemupukan' => $row->tanggal_pemupukan,
                'takaran' => (float) $row->takaran,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $paginator
        ]);
    }

    private function safeText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_convert_encoding((string) $value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
    }
}
