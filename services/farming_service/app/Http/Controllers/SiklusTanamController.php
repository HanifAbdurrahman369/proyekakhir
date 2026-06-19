<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SiklusTanam;
use App\Models\LahanSawah;
use App\Models\LaporPanen;
use App\Models\RiwayatPanen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SiklusTanamController extends Controller
{
    private const ROLE_KELOMPOK_TANI = 1;
    private const ROLE_PETUGAS = 2;
    private const ROLE_BRIGADE_PANGAN = 5;

    public function index(Request $request)
    {
        [$userId, $roleId] = $this->authUser($request);
        if (!$userId || !in_array($roleId, [self::ROLE_KELOMPOK_TANI, self::ROLE_BRIGADE_PANGAN], true)) {
            return $this->forbidden('Akses aktivitas tanam tidak diizinkan.');
        }

        $query = SiklusTanam::with(['bibit', 'lahan'])->orderByDesc('id');
        $this->batasiSiklusUntukPetani($query, $userId, $roleId);

        return response()->json([
            'success' => true,
            'data' => $query->get()->map(fn (SiklusTanam $item) => $this->formatSiklusTanam($item, $userId, $roleId)),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function store(Request $request)
    {
        $request->validate([
            'lahan_id' => 'required|integer',
            'bibit_id' => 'required|integer',
            'tanggal_tanam' => 'required|date|before_or_equal:today',
        ]);

        [$userId, $roleId] = $this->authUser($request);
        if (!$userId || !in_array($roleId, [self::ROLE_KELOMPOK_TANI, self::ROLE_BRIGADE_PANGAN], true)) {
            return $this->forbidden('Hanya Kelompok Tani dan Brigade Pangan yang dapat membuat laporan tanam.');
        }

        $lahan = $this->lahanTanamYangDiizinkan($userId, $roleId, (int) $request->lahan_id);

        if (!$lahan) {
            return $this->forbidden('Lahan tidak terdaftar sebagai lahan pemilik atau lahan penugasan Brigade Pangan.');
        }

        $aturan = $this->validasiAturanTanam($roleId, (int) $request->bibit_id, $request->tanggal_tanam);
        if ($aturan['error']) {
            return response()->json(['success' => false, 'message' => $aturan['error']], 422);
        }

        $siklusAktif = SiklusTanam::where('lahan_id', $lahan->id)
            ->where('status_aktif', 'AKTIF')
            ->exists();
        if ($siklusAktif) {
            return response()->json([
                'success' => false,
                'message' => 'Lahan masih memiliki proses tanam aktif. Selesaikan proses panen terlebih dahulu.',
            ], 422);
        }

        $bibit = $aturan['bibit'];
        $tanggalTanam = Carbon::parse($request->tanggal_tanam);
        
        $masaTanamHari = $request->has('estimasi_hari_tanam') ? (int) $request->estimasi_hari_tanam : (int) $bibit->masa_tanam_hari;
        $estimasiTanggalPanen = $tanggalTanam->copy()->addDays($masaTanamHari);

        $data = SiklusTanam::create([
            'lahan_id' => $lahan->id,
            'bibit_id' => $bibit->id,
            'tanggal_tanam' => $tanggalTanam->toDateString(),
            'estimasi_panen' => $masaTanamHari,
            'estimasi_tanggal_panen' => $estimasiTanggalPanen->toDateString(),
            'tanggal_panen' => null,
            'hasil_panen' => null,
            'status_aktif' => 'AKTIF',
            'status_verifikasi' => 'PENDING',
            'created_by' => $userId,
            'peran_pelapor' => $roleId === self::ROLE_BRIGADE_PANGAN ? 'brigade_pangan' : 'kelompok_tani',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan tanam berhasil disimpan. Estimasi panen dihitung otomatis dari masa varietas.',
            'data' => $this->formatSiklusTanam($data->load(['bibit', 'lahan']), $userId, $roleId),
        ], 201, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function storeLaporPanen(Request $request)
    {
        $request->validate([
            'siklus_tanam_id' => 'required|integer',
            'tanggal_panen' => 'required|date',
            'hasil_panen' => 'required|numeric|min:0',
        ]);

        [$userId, $roleId] = $this->authUser($request);
        if (!$userId || $roleId !== self::ROLE_KELOMPOK_TANI) {
            return $this->forbidden('Laporan hasil panen hanya dapat dibuat oleh Kelompok Tani sebagai pemilik lahan.');
        }

        $siklus = SiklusTanam::query()
            ->join('lahan_sawah as ls', 'ls.id', '=', 'siklus_tanam.lahan_id')
            ->where('siklus_tanam.id', $request->siklus_tanam_id)
            ->where('ls.user_id', $userId)
            ->where('siklus_tanam.status_aktif', 'AKTIF')
            ->select('siklus_tanam.*')
            ->first();

        if (!$siklus) {
            return response()->json([
                'success' => false,
                'message' => 'Siklus tanam tidak ditemukan, sudah selesai, atau lahan bukan milik Anda.'
            ], 403);
        }

        $tanggalPanen = Carbon::parse($request->tanggal_panen);
        $estimasi = $siklus->estimasi_tanggal_panen
            ? Carbon::parse($siklus->estimasi_tanggal_panen)
            : Carbon::parse($siklus->tanggal_tanam)->addDays((int) $siklus->estimasi_panen);

        if ($tanggalPanen->lt($estimasi)) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan panen belum dapat dibuat sebelum estimasi panen ' . $estimasi->format('d-m-Y') . '.',
            ], 422);
        }

        if ($tanggalPanen->isFuture()) {
            return response()->json(['success' => false, 'message' => 'Tanggal panen tidak boleh melebihi hari ini.'], 422);
        }

        $laporanAktif = LaporPanen::where('siklus_tanam_id', $siklus->id)
            ->whereIn('status_verifikasi', ['PENDING', 'DITERIMA'])
            ->exists();
        if ($laporanAktif) {
            return response()->json([
                'success' => false,
                'message' => 'Siklus ini sudah memiliki laporan panen yang sedang atau telah diverifikasi.',
            ], 422);
        }

        $data = LaporPanen::create([
            'siklus_tanam_id' => $request->siklus_tanam_id,
            'tanggal_panen' => $request->tanggal_panen,
            'hasil_panen' => $request->hasil_panen,
            'estimasi_panen' => $siklus->estimasi_panen,
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
            'data' => $this->ambilDetailHasilPanen((int) $data->id),
        ], 201);
    }

    public function updateLaporPanen(Request $request, $id)
    {
        $request->validate([
            'tanggal_panen' => 'required|date',
            'hasil_panen' => 'required|numeric|min:0.01',
        ]);

        [$userId, $roleId] = $this->authUser($request);
        if (!$userId || $roleId !== self::ROLE_KELOMPOK_TANI) {
            return $this->forbidden('Perbaikan laporan panen hanya dapat dilakukan Kelompok Tani.');
        }

        $laporan = LaporPanen::query()
            ->join('siklus_tanam as st', 'st.id', '=', 'lapor_panen.siklus_tanam_id')
            ->join('lahan_sawah as ls', 'ls.id', '=', 'st.lahan_id')
            ->where('lapor_panen.id', $id)
            ->where('ls.user_id', $userId)
            ->where('lapor_panen.status_verifikasi', 'DITOLAK')
            ->select('lapor_panen.*', 'st.estimasi_tanggal_panen')
            ->first();

        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan panen ditolak tidak ditemukan atau bukan milik lahan Anda.',
            ], 404);
        }

        $tanggalPanen = Carbon::parse($request->tanggal_panen);
        if ($laporan->estimasi_tanggal_panen && $tanggalPanen->lt(Carbon::parse($laporan->estimasi_tanggal_panen))) {
            return response()->json(['success' => false, 'message' => 'Tanggal panen masih sebelum estimasi panen.'], 422);
        }
        if ($tanggalPanen->isFuture()) {
            return response()->json(['success' => false, 'message' => 'Tanggal panen tidak boleh melebihi hari ini.'], 422);
        }

        DB::table('lapor_panen')->where('id', $id)->update([
            'tanggal_panen' => $tanggalPanen->toDateString(),
            'hasil_panen' => $request->hasil_panen,
            'status_verifikasi' => 'PENDING',
            'catatan_verifikasi' => null,
            'verified_by' => null,
            'verified_at' => null,
            'updated_at' => now(),
        ]);

        $this->buatNotifikasiPetugas(
            'Perbaikan Laporan Panen',
            'Kelompok Tani mengajukan ulang laporan panen yang telah diperbaiki.',
            'lapor_panen',
            (int) $id,
            '/verifikasi-data-petani?tipe=panen&id=' . $id
        );

        return response()->json([
            'success' => true,
            'message' => 'Laporan panen berhasil diperbaiki dan diajukan ulang.',
            'data' => $this->ambilDetailHasilPanen((int) $id),
        ]);
    }

    public function showLaporPanen(Request $request, $id)
    {
        [$userId, $roleId] = $this->authUser($request);
        $query = $this->queryHasilPanenAman()->where('lp.id', $id);

        if ($roleId === self::ROLE_KELOMPOK_TANI) {
            $query->where('ls.user_id', $userId);
        } elseif ($roleId !== self::ROLE_PETUGAS) {
            return $this->forbidden('Detail laporan panen tidak dapat diakses.');
        }

        $row = $query->first();
        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Laporan panen tidak ditemukan.'], 404);
        }

        return response()->json(['success' => true, 'data' => $this->formatHasilPanen($row)]);
    }

    public function show(Request $request, $id)
    {
        [$userId, $roleId] = $this->authUser($request);
        $query = SiklusTanam::with(['bibit', 'lahan'])->where('id', $id);
        $this->batasiSiklusUntukPetani($query, $userId, $roleId);
        $data = $query->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatSiklusTanam($data, $userId, $roleId),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function update(Request $request, $id)
    {
        [$userId, $roleId] = $this->authUser($request);
        if (!$userId || !in_array($roleId, [self::ROLE_KELOMPOK_TANI, self::ROLE_BRIGADE_PANGAN], true)) {
            return $this->forbidden('Anda tidak memiliki akses untuk mengubah laporan tanam.');
        }

        $data = SiklusTanam::where('id', $id)->where('created_by', $userId)->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        if ($data->status_aktif === 'NONAKTIF' || LaporPanen::where('siklus_tanam_id', $data->id)->where('status_verifikasi', 'DITERIMA')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Siklus tanam yang telah dipanen tidak boleh diubah.'
            ], 400);
        }

        $request->validate([
            'lahan_id' => 'required|integer',
            'bibit_id' => 'required|integer',
            'tanggal_tanam' => 'required|date|before_or_equal:today',
        ]);

        $lahan = $this->lahanTanamYangDiizinkan($userId, $roleId, (int) $request->lahan_id);
        if (!$lahan) {
            return $this->forbidden('Lahan tidak tersedia untuk akun ini.');
        }

        $aturan = $this->validasiAturanTanam($roleId, (int) $request->bibit_id, $request->tanggal_tanam);
        if ($aturan['error']) {
            return response()->json(['success' => false, 'message' => $aturan['error']], 422);
        }

        $bibit = $aturan['bibit'];
        $tanggalTanam = Carbon::parse($request->tanggal_tanam);

        $data->update([
            'lahan_id' => $lahan->id,
            'bibit_id' => $bibit->id,
            'tanggal_tanam' => $tanggalTanam->toDateString(),
            'estimasi_panen' => (int) $bibit->masa_tanam_hari,
            'estimasi_tanggal_panen' => $tanggalTanam->copy()->addDays((int) $bibit->masa_tanam_hari)->toDateString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan tanam berhasil diperbarui.',
            'data' => $this->formatSiklusTanam($data->fresh(['bibit', 'lahan']), $userId, $roleId),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function totalProduksi(Request $request)
    {
        [$userId, $roleId] = $this->authUser($request);
        $tahun = Carbon::now()->year;

        $query = RiwayatPanen::whereYear('tanggal_panen', $tahun)
            ->whereDate('tanggal_panen', '<=', now()->toDateString());
        if ($roleId === self::ROLE_KELOMPOK_TANI) {
            $query->where('pemilik_user_id', $userId);
        } elseif ($roleId === self::ROLE_BRIGADE_PANGAN) {
            $query->where('penggarap_user_id', $userId);
        } else {
            return $this->forbidden('Ringkasan produksi hanya tersedia untuk petani.');
        }

        $total = $query->sum('hasil_panen_ton');

        return response()->json([
            'success' => true,
            'data' => [
                'tahun' => $tahun,
                'total_produksi' => (float) $total
            ]
        ]);
    }

    public function getPendingVerifications(Request $request)
    {
        [, $roleId] = $this->authUser($request);
        if ($roleId !== self::ROLE_PETUGAS) {
            return $this->forbidden('Antrean verifikasi hanya dapat diakses petugas.');
        }

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
            return $this->approve($request, $id);
        }

        if (in_array($aksi, ['DITOLAK', 'TOLAK', 'REJECT', 'REJECTED'], true)) {
            return $this->reject($request, $id);
        }

        return response()->json([
            'success' => false,
            'message' => 'Aksi verifikasi tidak valid. Gunakan DITERIMA atau DITOLAK.',
        ], 422);
    }

    public function approve(Request $request, $id)
    {
        [$petugasId, $roleId] = $this->authUser($request);
        if (!$petugasId || $roleId !== self::ROLE_PETUGAS) {
            return $this->forbidden('Verifikasi hasil panen hanya dapat dilakukan petugas.');
        }

        $result = DB::transaction(function () use ($id, $petugasId) {
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

            if ($data->status_verifikasi === 'DITERIMA') {
                return [
                    'status' => 409,
                    'body' => ['success' => false, 'message' => 'Laporan panen sudah pernah disetujui.'],
                ];
            }

            $data->update([
                'status_verifikasi' => 'DITERIMA',
                'catatan_verifikasi' => null,
                'verified_by' => $petugasId,
                'verified_at' => now(),
            ]);

            $siklus = SiklusTanam::where('id', $data->siklus_tanam_id)->lockForUpdate()->first();
            if (!$siklus) {
                throw new \RuntimeException('Siklus tanam untuk laporan panen tidak ditemukan.');
            }

            $siklus->update([
                'tanggal_panen' => $data->tanggal_panen,
                'hasil_panen' => $data->hasil_panen,
                'status_aktif' => 'NONAKTIF',
                'status_verifikasi' => 'DITERIMA',
                'verified_by' => $petugasId,
                'verified_at' => now(),
                'catatan_verifikasi' => null,
            ]);

            $this->arsipkanPanen($data, $siklus, $petugasId);
            $this->sinkronkanInfoLahanDariPanenTerakhir((int) $siklus->lahan_id);

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

    public function reject(Request $request, $id)
    {
        [$petugasId, $roleId] = $this->authUser($request);
        if (!$petugasId || $roleId !== self::ROLE_PETUGAS) {
            return $this->forbidden('Verifikasi hasil panen hanya dapat dilakukan petugas.');
        }

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
            'catatan_verifikasi' => $request->input('alasan_penolakan') ?? $request->input('catatan_verifikasi'),
            'verified_by' => $petugasId,
            'verified_at' => now(),
        ]);

        $this->tandaiNotifikasiPanenTerbaca((int) $data->id, 'lapor_panen');

        return response()->json([
            'success' => true,
            'message' => 'Laporan panen berhasil ditolak',
            'data' => $this->ambilDetailHasilPanen((int) $data->id),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function destroy(Request $request, $id)
    {
        [$userId, $roleId] = $this->authUser($request);
        if (!$userId || !in_array($roleId, [self::ROLE_KELOMPOK_TANI, self::ROLE_BRIGADE_PANGAN], true)) {
            return $this->forbidden('Anda tidak memiliki akses untuk menghapus laporan tanam.');
        }

        $data = SiklusTanam::where('id', $id)->where('created_by', $userId)->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan'
            ], 404);
        }

        if ($data->status_aktif === 'NONAKTIF' || LaporPanen::where('siklus_tanam_id', $data->id)->whereIn('status_verifikasi', ['PENDING', 'DITERIMA'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Siklus yang sedang menunggu verifikasi atau telah dipanen tidak boleh dihapus.'
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
            ->leftJoin('users as u', 'u.id', '=', 'lp.created_by')
            ->leftJoin('users as ug', 'ug.id', '=', 'st.created_by')
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
                'ug.nama_lengkap as nama_penggarap',
                'st.created_by as penggarap_user_id',
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
            'nama_penggarap' => $this->safeText($row->nama_penggarap),
            'penggarap_user_id' => (int) $row->penggarap_user_id,
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

        $panenTerakhir = RiwayatPanen::where('lahan_id', $lahanId)
            ->where('status_verifikasi', 'DITERIMA')
            ->whereDate('tanggal_panen', '<=', now()->toDateString())
            ->orderByDesc('tanggal_panen')
            ->orderByDesc('id')
            ->first();

        if (!$panenTerakhir) {
            return;
        }

        $hasilPanen = (float) $panenTerakhir->hasil_panen_ton;

        $payload = [
            'hasil_panen_ton' => round($hasilPanen, 2),
            'produktivitas_ton_ha' => round((float) $panenTerakhir->produktivitas_ton_ha, 2),
            'hasil_panen_siklus_id' => $panenTerakhir->siklus_tanam_id,
            'riwayat_panen_terakhir_id' => $panenTerakhir->id,
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
        [$userId, $roleId] = $this->authUser($request);
        if (!$userId || !in_array($roleId, [self::ROLE_KELOMPOK_TANI, self::ROLE_BRIGADE_PANGAN], true)) {
            return $this->forbidden('Daftar proses tanam hanya tersedia untuk petani.');
        }

        $query = SiklusTanam::with(['lahan', 'bibit'])->orderByDesc('id');
        $this->batasiSiklusUntukPetani($query, $userId, $roleId);
        $data = $query->get()->map(fn (SiklusTanam $item) => $this->formatSiklusTanam($item, $userId, $roleId));

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

        [$userId, $roleId] = $this->authUser($request);
        if (!$userId || !in_array($roleId, [self::ROLE_KELOMPOK_TANI, self::ROLE_BRIGADE_PANGAN], true)) {
            return $this->forbidden('Catatan pemupukan hanya tersedia untuk petani.');
        }

        $query = SiklusTanam::with('lahan')->where('id', $request->siklus_tanam_id);
        $this->batasiSiklusUntukPetani($query, $userId, $roleId);
        $siklus = $query->where('status_aktif', 'AKTIF')->first();

        if (!$siklus) {
            return response()->json([
                'success' => false,
                'message' => 'Siklus tanam tidak valid.'
            ], 403);
        }

        $tanggalPupuk = Carbon::parse($request->tanggal_pemupukan);
        if ($tanggalPupuk->lt(Carbon::parse($siklus->tanggal_tanam)) || $tanggalPupuk->isFuture()) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal pemupukan harus berada setelah tanggal tanam dan tidak boleh melebihi hari ini.',
            ], 422);
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
        [$userId, $roleId] = $this->authUser($request);
        if (!$userId || !in_array($roleId, [self::ROLE_KELOMPOK_TANI, self::ROLE_BRIGADE_PANGAN], true)) {
            return $this->forbidden('Riwayat pemupukan hanya tersedia untuk petani.');
        }

        $perPage = $request->get('per_page', 3);
        $query = DB::table('siklus_pupuk as sp')
            ->join('siklus_tanam as st', 'sp.siklus_tanam_id', '=', 'st.id')
            ->join('lahan_sawah as ls', 'st.lahan_id', '=', 'ls.id')
            ->join('jenis_pupuk as jp', 'sp.pupuk_id', '=', 'jp.id')
            ->select([
                'sp.id',
                'ls.nama_lahan',
                'jp.nama_bibit as nama_pupuk',
                'jp.varietas as tipe_pupuk',
                'sp.tanggal_pemupukan',
                'sp.takaran'
            ]);

        if ($roleId === self::ROLE_KELOMPOK_TANI) {
            $query->where('ls.user_id', $userId);
        } else {
            $query->where('st.created_by', $userId);
        }

        $paginator = $query
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

    private function authUser(Request $request): array
    {
        $user = $request->attributes->get('auth');

        return [
            (int) ($user->sub ?? $user->id ?? 0),
            (int) ($user->role_id ?? $user->role ?? 0),
        ];
    }

    private function forbidden(string $message)
    {
        return response()->json(['success' => false, 'message' => $message], 403);
    }

    private function batasiSiklusUntukPetani($query, int $userId, int $roleId): void
    {
        if ($roleId === self::ROLE_KELOMPOK_TANI) {
            $query->whereHas('lahan', fn ($q) => $q->where('user_id', $userId));
            return;
        }

        if ($roleId === self::ROLE_BRIGADE_PANGAN) {
            $query->where('created_by', $userId);
            return;
        }

        $query->whereRaw('1 = 0');
    }

    private function lahanTanamYangDiizinkan(int $userId, int $roleId, int $lahanId): ?LahanSawah
    {
        $query = LahanSawah::where('id', $lahanId)->where('status_verifikasi', 'DITERIMA');

        if ($roleId === self::ROLE_KELOMPOK_TANI) {
            return $query->where('user_id', $userId)->first();
        }

        if ($roleId !== self::ROLE_BRIGADE_PANGAN) {
            return null;
        }

        $indukId = DB::table('users as u')
            ->join('kelompok as k', 'k.id', '=', 'u.kelompok_id')
            ->where('u.id', $userId)
            ->where('k.jenis_kelompok', 'brigade_pangan')
            ->where('k.status_keanggotaan', 'AKTIF')
            ->value('k.kelompok_tani_induk_id');

        if (!$indukId) {
            return null;
        }

        $pemilikIds = DB::table('users')
            ->where('role_id', self::ROLE_KELOMPOK_TANI)
            ->where('kelompok_id', $indukId)
            ->pluck('id');

        return $query->whereIn('user_id', $pemilikIds)->first();
    }

    private function validasiAturanTanam(int $roleId, int $bibitId, string $tanggalTanam): array
    {
        $bibit = DB::table('jenis_bibit')->where('id', $bibitId)->first();
        if (!$bibit) {
            return ['error' => 'Jenis bibit tidak ditemukan.', 'bibit' => null];
        }

        $bulan = (int) Carbon::parse($tanggalTanam)->format('n');
        $varietas = mb_strtolower((string) $bibit->varietas);

        if ($roleId === self::ROLE_KELOMPOK_TANI) {
            if ($varietas !== 'lokal') {
                return ['error' => 'Kelompok Tani hanya dapat menggunakan bibit lokal.', 'bibit' => $bibit];
            }
            if ($bulan < 1 || $bulan > 9) {
                return ['error' => 'Masa tanam Kelompok Tani berlaku pada Januari sampai September.', 'bibit' => $bibit];
            }
        }

        if ($roleId === self::ROLE_BRIGADE_PANGAN) {
            if ($varietas !== 'unggul') {
                return ['error' => 'Brigade Pangan hanya dapat menggunakan bibit unggul.', 'bibit' => $bibit];
            }
            if (!in_array($bulan, [10, 11, 12, 1], true)) {
                return ['error' => 'Masa tanam Brigade Pangan berlaku pada Oktober sampai Januari.', 'bibit' => $bibit];
            }
        }

        return ['error' => null, 'bibit' => $bibit];
    }

    private function formatSiklusTanam(SiklusTanam $item, int $userId, int $roleId): array
    {
        $tanggalTanam = Carbon::parse($item->tanggal_tanam);
        $estimasi = $item->estimasi_tanggal_panen
            ? Carbon::parse($item->estimasi_tanggal_panen)
            : $tanggalTanam->copy()->addDays((int) ($item->estimasi_panen ?: $item->bibit?->masa_tanam_hari));
        $totalHari = max(1, $tanggalTanam->diffInDays($estimasi));
        $hariBerjalan = max(0, $tanggalTanam->diffInDays(now(), false));
        $progress = (int) min(100, max(0, round(($hariBerjalan / $totalHari) * 100)));
        $laporan = LaporPanen::where('siklus_tanam_id', $item->id)->latest('id')->first();
        $milikPemilik = (int) ($item->lahan?->user_id ?? 0) === $userId;

        return [
            'id' => (int) $item->id,
            'lahan_id' => (int) $item->lahan_id,
            'bibit_id' => (int) $item->bibit_id,
            'tanggal_tanam' => optional($item->tanggal_tanam)->format('Y-m-d'),
            'estimasi_panen' => (int) $item->estimasi_panen,
            'estimasi_tanggal_panen' => $estimasi->toDateString(),
            'status_aktif' => $item->status_aktif,
            'tanggal_panen' => optional($item->tanggal_panen)->format('Y-m-d'),
            'hasil_panen' => $item->hasil_panen,
            'status_verifikasi' => $item->status_verifikasi,
            'peran_pelapor' => $item->peran_pelapor,
            'created_by' => (int) $item->created_by,
            'nama_lahan' => $item->lahan?->nama_lahan ?? '-',
            'pemilik_lahan' => $item->lahan?->pemilik_lahan ?? '-',
            'nama_bibit' => $item->bibit?->nama_bibit ?? '-',
            'varietas' => $item->bibit?->varietas,
            'masa_tanam_hari' => (int) ($item->bibit?->masa_tanam_hari ?? $item->estimasi_panen),
            'progress_persen' => $progress,
            'hari_tersisa' => max(0, now()->startOfDay()->diffInDays($estimasi->startOfDay(), false)),
            'status_laporan_panen' => $laporan?->status_verifikasi,
            'can_edit' => (int) $item->created_by === $userId && $item->status_aktif === 'AKTIF' && !$laporan,
            'can_delete' => (int) $item->created_by === $userId && $item->status_aktif === 'AKTIF' && !$laporan,
            'can_report_harvest' => $roleId === self::ROLE_KELOMPOK_TANI
                && $milikPemilik
                && $item->status_aktif === 'AKTIF'
                && now()->startOfDay()->gte($estimasi->startOfDay())
                && (!$laporan || $laporan->status_verifikasi === 'DITOLAK'),
        ];
    }

    private function arsipkanPanen(LaporPanen $laporan, SiklusTanam $siklus, int $petugasId): void
    {
        $lahan = LahanSawah::findOrFail($siklus->lahan_id);
        $bibit = DB::table('jenis_bibit')->where('id', $siklus->bibit_id)->first();
        $luas = (float) ($lahan->luas_lahan_hektar ?? 0);
        $hasil = (float) $laporan->hasil_panen;

        RiwayatPanen::updateOrCreate(
            ['lapor_panen_id' => $laporan->id],
            [
                'siklus_tanam_id' => $siklus->id,
                'lahan_id' => $lahan->id,
                'bibit_id' => $siklus->bibit_id,
                'pemilik_user_id' => $lahan->user_id,
                'penggarap_user_id' => $siklus->created_by,
                'diverifikasi_oleh' => $petugasId,
                'nama_lahan' => $lahan->nama_lahan,
                'nama_bibit' => $bibit->nama_bibit ?? '-',
                'varietas' => $bibit->varietas ?? null,
                'tanggal_tanam' => $siklus->tanggal_tanam,
                'tanggal_panen' => $laporan->tanggal_panen,
                'hasil_panen_ton' => $hasil,
                'luas_lahan_ha' => $luas,
                'produktivitas_ton_ha' => $luas > 0 ? round($hasil / $luas, 2) : 0,
                'status_verifikasi' => 'DITERIMA',
                'diverifikasi_at' => now(),
            ]
        );
    }

    private function safeText($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_convert_encoding((string) $value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
    }
}
