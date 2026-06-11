<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SiklusTanam;
use App\Models\LahanSawah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class SiklusTanamController extends Controller
{
    public function index()
    {
        $user = request()->attributes->get('auth');

        $query = $this->queryHasilPanenAman();

        if ($user && isset($user->role_id) && (int) $user->role_id === 1) {
            $query->where('st.created_by', $user->sub);
        }

        return response()->json([
            'success' => true,
            'data' => $query->orderByDesc('st.id')->get()->map(fn ($row) => $this->formatHasilPanen($row))->values(),
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

        if (!$user || !isset($user->sub)) {
            return response()->json([
                'success' => false,
                'message' => 'Token pengguna tidak valid. Silakan login ulang.',
            ], 401);
        }

        $lahan = LahanSawah::where('id', $request->lahan_id)
            ->where('user_id', $user->sub)
            ->where('status_verifikasi', 'DITERIMA')
            ->first();

        if (!$lahan) {
            return response()->json([
                'success' => false,
                'message' => 'Lahan tidak valid atau belum diverifikasi petugas.',
            ], 403);
        }

        $data = DB::transaction(function () use ($request, $user, $lahan) {
            $data = SiklusTanam::create([
                'lahan_id' => $request->lahan_id,
                'bibit_id' => $request->bibit_id,
                'tanggal_tanam' => $request->tanggal_tanam,
                'estimasi_panen' => $this->normalisasiEstimasiPanen($request->estimasi_panen),
                'tanggal_panen' => $request->tanggal_panen,
                'hasil_panen' => $request->hasil_panen,
                'status_aktif' => 'AKTIF',
                'status_verifikasi' => 'PENDING',
                'created_by' => $user->sub,
            ]);

            $this->buatNotifikasiPetugasPanen(
                (int) $data->id,
                'Laporan Hasil Panen Baru',
                'Petani mengirim laporan panen untuk lahan ' . $this->safeText($lahan->nama_lahan) . '. Segera lakukan verifikasi.',
                $user->sub
            );

            return $data;
        });

        $row = $this->queryHasilPanenAman()
            ->where('st.id', $data->id)
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Laporan hasil panen berhasil dikirim dan menunggu verifikasi petugas.',
            'data' => $row ? $this->formatHasilPanen($row) : $data,
        ], 201, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function show($id)
    {
        $row = $this->queryHasilPanenAman()
            ->where('st.id', $id)
            ->first();

        if (!$row) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatHasilPanen($row),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function update(Request $request, $id)
    {
        $data = SiklusTanam::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        if ($data->status_verifikasi === 'DITERIMA') {
            return response()->json([
                'success' => false,
                'message' => 'Data yang sudah diverifikasi tidak boleh diubah',
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

        $user = $request->attributes->get('auth');

        if ($user && isset($user->sub)) {
            $lahan = LahanSawah::where('id', $request->lahan_id)
                ->where('user_id', $user->sub)
                ->where('status_verifikasi', 'DITERIMA')
                ->first();

            if (!$lahan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lahan tidak valid atau belum diverifikasi petugas.',
                ], 403);
            }
        }

        $data->update([
            'lahan_id' => $request->lahan_id,
            'bibit_id' => $request->bibit_id,
            'tanggal_tanam' => $request->tanggal_tanam,
            'estimasi_panen' => $this->normalisasiEstimasiPanen($request->estimasi_panen),
            'tanggal_panen' => $request->tanggal_panen,
            'hasil_panen' => $request->hasil_panen,
            'status_verifikasi' => 'PENDING',
        ]);

        $row = $this->queryHasilPanenAman()
            ->where('st.id', $id)
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Laporan hasil panen berhasil diperbarui dan kembali menunggu verifikasi petugas.',
            'data' => $row ? $this->formatHasilPanen($row) : $data->fresh(),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function totalProduksi(Request $request)
    {
        $user = $request->attributes->get('auth');
        $tahun = Carbon::now()->year;

        $query = SiklusTanam::where('status_verifikasi', 'DITERIMA')
            ->whereYear('tanggal_panen', $tahun);

        if ($user && isset($user->sub)) {
            $query->where('created_by', $user->sub);
        }

        $total = $query->sum('hasil_panen');

        return response()->json([
            'success' => true,
            'data' => [
                'tahun' => $tahun,
                'total_produksi' => round((float) $total, 2),
            ],
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

            if ($data->status_verifikasi === 'DITERIMA') {
                $this->sinkronkanLahanDariPanenDiterima((int) $data->lahan_id);

                return [
                    'status' => 200,
                    'body' => [
                        'success' => true,
                        'message' => 'Data hasil panen sudah diterima sebelumnya. Data lahan sudah disinkronkan ulang.',
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

            $this->sinkronkanLahanDariPanenDiterima((int) $data->lahan_id);
            $this->tandaiNotifikasiPanenTerbaca((int) $data->id);

            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'message' => 'Hasil panen berhasil diterima. Status panen berubah menjadi legal dan data lahan sawah sudah diperbarui.',
                ],
            ];
        });

        return response()->json($result['body'], $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function reject($id)
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

            if ($data->status_verifikasi === 'DITOLAK') {
                return [
                    'status' => 200,
                    'body' => [
                        'success' => true,
                        'message' => 'Data hasil panen sudah ditolak sebelumnya',
                    ],
                ];
            }

            $data->update([
                'status_verifikasi' => 'DITOLAK',
            ]);

            $this->tandaiNotifikasiPanenTerbaca((int) $data->id);

            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'message' => 'Hasil panen berhasil ditolak. Data lahan sawah tidak diperbarui.',
                ],
            ];
        });

        return response()->json($result['body'], $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function destroy($id)
    {
        $data = SiklusTanam::find($id);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        if ($data->status_verifikasi === 'DITERIMA') {
            return response()->json([
                'success' => false,
                'message' => 'Data yang sudah diverifikasi tidak boleh dihapus',
            ], 400);
        }

        $data->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus',
        ]);
    }

    private function queryHasilPanenAman()
    {
        return DB::table('siklus_tanam as st')
            ->leftJoin('lahan_sawah as ls', 'ls.id', '=', 'st.lahan_id')
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
                'ls.hasil_panen_ton',
                'ls.produktivitas_ton_ha',
                'ls.alamat_detail',
                'ls.latitude',
                'ls.longitude',
                'u.nama_lengkap as nama_petani',
                'u.email as email_petani',
                'jb.nama_bibit',
                'jb.varietas',
                'kc.nama_kecamatan',
                'kl.nama_kelurahan',
            ]);
    }

    private function formatHasilPanen($row): array
    {
        return [
            'id' => (int) $row->id,
            'lahan_id' => $row->lahan_id !== null ? (int) $row->lahan_id : null,
            'bibit_id' => $row->bibit_id !== null ? (int) $row->bibit_id : null,
            'tanggal_tanam' => $row->tanggal_tanam,
            'estimasi_panen' => $row->estimasi_panen,
            'status_aktif' => $row->status_aktif,
            'tanggal_panen' => $row->tanggal_panen,
            'hasil_panen' => $row->hasil_panen !== null ? (float) $row->hasil_panen : null,
            'status_verifikasi' => $row->status_verifikasi,
            'created_by' => $row->created_by !== null ? (int) $row->created_by : null,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'nama_lahan' => $this->safeText($row->nama_lahan),
            'pemilik_lahan' => $this->safeText($row->pemilik_lahan),
            'luas_lahan_hektar' => $row->luas_lahan_hektar !== null ? (float) $row->luas_lahan_hektar : 0,
            'hasil_panen_ton_lahan' => $row->hasil_panen_ton !== null ? (float) $row->hasil_panen_ton : 0,
            'produktivitas_ton_ha' => $row->produktivitas_ton_ha !== null ? (float) $row->produktivitas_ton_ha : 0,
            'alamat_detail' => $this->safeText($row->alamat_detail),
            'latitude' => $row->latitude,
            'longitude' => $row->longitude,
            'nama_petani' => $this->safeText($row->nama_petani),
            'email_petani' => $this->safeText($row->email_petani),
            'nama_bibit' => $this->safeText($row->nama_bibit),
            'varietas' => $this->safeText($row->varietas),
            'nama_kecamatan' => $this->safeText($row->nama_kecamatan),
            'nama_kelurahan' => $this->safeText($row->nama_kelurahan),
        ];
    }

    private function sinkronkanLahanDariPanenDiterima(int $lahanId): void
    {
        $lahan = LahanSawah::where('id', $lahanId)->lockForUpdate()->first();

        if (!$lahan) {
            return;
        }

        $totalPanen = (float) SiklusTanam::where('lahan_id', $lahanId)
            ->where('status_verifikasi', 'DITERIMA')
            ->whereNotNull('hasil_panen')
            ->sum('hasil_panen');

        $luas = (float) ($lahan->luas_lahan_hektar ?? 0);
        $produktivitas = $luas > 0 ? $totalPanen / $luas : 0;

        $payload = [
            'hasil_panen_ton' => round($totalPanen, 2),
            'produktivitas_ton_ha' => round($produktivitas, 2),
        ];

        if (Schema::hasColumn('lahan_sawah', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table('lahan_sawah')
            ->where('id', $lahanId)
            ->update($payload);
    }

    private function buatNotifikasiPetugasPanen(int $siklusId, string $judul, string $pesan, ?int $userPetaniId = null): void
    {
        try {
            $payload = [
                'role_id_penerima' => 2,
                'user_id_penerima' => null,
                'judul' => $this->safeText($judul),
                'pesan' => $this->safeText($pesan),
                'is_read' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('notifikasi', 'ref_type')) {
                $payload['ref_type'] = 'siklus_tanam';
            }

            if (Schema::hasColumn('notifikasi', 'ref_id')) {
                $payload['ref_id'] = $siklusId;
            }

            if (Schema::hasColumn('notifikasi', 'target_url')) {
                $payload['target_url'] = '/verifikasi-data-petani?tipe=panen&id=' . $siklusId;
            }

            DB::table('notifikasi')->insert($payload);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat notifikasi petugas hasil panen: ' . $e->getMessage(), [
                'siklus_tanam_id' => $siklusId,
                'user_petani_id' => $userPetaniId,
            ]);
        }
    }

    private function tandaiNotifikasiPanenTerbaca(int $siklusId): void
    {
        try {
            if (Schema::hasColumn('notifikasi', 'ref_type') && Schema::hasColumn('notifikasi', 'ref_id')) {
                DB::table('notifikasi')
                    ->where('ref_type', 'siklus_tanam')
                    ->where('ref_id', $siklusId)
                    ->update([
                        'is_read' => 1,
                        'updated_at' => now(),
                    ]);
            }
        } catch (\Throwable $e) {
            Log::error('Gagal menandai notifikasi panen sebagai dibaca: ' . $e->getMessage(), [
                'siklus_tanam_id' => $siklusId,
            ]);
        }
    }

    private function normalisasiEstimasiPanen($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return $value;
        }

        try {
            return Carbon::parse($value)->format('Ymd');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function safeText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value) || is_numeric($value)) {
            return (string) $value;
        }

        $text = (string) $value;

        if (function_exists('mb_check_encoding') && !mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }

        return $text;
    }
}
