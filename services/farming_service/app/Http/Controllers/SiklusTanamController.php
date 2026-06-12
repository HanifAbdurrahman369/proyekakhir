<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SiklusTanam;
use App\Models\LahanSawah;
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
            ->where('st.status_verifikasi', 'PENDING')
            ->whereNotNull('st.hasil_panen')
            ->orderByDesc('st.created_at')
            ->orderByDesc('st.id')
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
            $data = SiklusTanam::where('id', $id)->lockForUpdate()->first();

            if (!$data) {
                return [
                    'status' => 404,
                    'body' => [
                        'success' => false,
                        'message' => 'Data hasil panen tidak ditemukan',
                    ],
                ];
            }

            if ($data->hasil_panen === null || $data->tanggal_panen === null) {
                return [
                    'status' => 422,
                    'body' => [
                        'success' => false,
                        'message' => 'Data belum memiliki tanggal panen atau hasil panen.',
                    ],
                ];
            }

            $data->update([
                'status_verifikasi' => 'DITERIMA',
                'status_aktif' => 'NONAKTIF',
            ]);

            $this->sinkronkanInfoLahanDariPanenTerakhir((int) $data->lahan_id);
            $this->tandaiNotifikasiPanenTerbaca((int) $data->id);

            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'message' => 'Hasil panen berhasil disetujui. Status panen menjadi DITERIMA dan info lahan sawah sudah diperbarui.',
                    'data' => $this->ambilDetailHasilPanen((int) $data->id),
                ],
            ];
        });

        return response()->json($result['body'], $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function reject($id)
    {
        $data = SiklusTanam::find($id);

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
            'status_verifikasi' => 'DITOLAK'
        ]);

        $this->tandaiNotifikasiPanenTerbaca((int) $data->id);

        return response()->json([
            'success' => true,
            'message' => 'Hasil panen berhasil ditolak',
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
        return DB::table('siklus_tanam as st')
            ->join('lahan_sawah as ls', 'ls.id', '=', 'st.lahan_id')
            ->leftJoin('users as u', 'u.id', '=', 'st.created_by')
            ->leftJoin('jenis_bibit as jb', 'jb.id', '=', 'st.bibit_id')
            ->leftJoin('kecamatan as kc', 'kc.id', '=', 'ls.kecamatan_id')
            ->leftJoin('kelurahan as kl', 'kl.id', '=', 'ls.kelurahan_id')
            ->select([
                'st.id',
                'st.lahan_id',
                'st.bibit_id',
                'st.tanggal_tanam',
                'st.estimasi_panen',
                'st.status_aktif',
                'st.tanggal_panen',
                'st.hasil_panen',
                'st.status_verifikasi',
                'st.created_by',
                'st.created_at',
                'st.updated_at',
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
        $row = $this->queryHasilPanenAman()->where('st.id', $id)->first();

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
            'lahan_id' => (int) $row->lahan_id,
            'bibit_id' => (int) $row->bibit_id,
            'tanggal_tanam' => $row->tanggal_tanam,
            'estimasi_panen' => $row->estimasi_panen,
            'status_aktif' => $row->status_aktif,
            'tanggal_panen' => $row->tanggal_panen,
            'hasil_panen' => $hasilPanen,
            'hasil_panen_label' => number_format((float) ($hasilPanen ?? 0), 2, ',', '.') . ' Ton',
            'status_verifikasi' => $row->status_verifikasi,
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

    private function tandaiNotifikasiPanenTerbaca(int $siklusId): void
    {
        try {
            if ($this->kolomAda('notifikasi', 'ref_type') && $this->kolomAda('notifikasi', 'ref_id')) {
                DB::table('notifikasi')
                    ->where('ref_type', 'siklus_tanam')
                    ->where('ref_id', $siklusId)
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

    private function safeText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_convert_encoding((string) $value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
    }
}
