<?php

namespace App\Http\Controllers;

use App\Models\LahanSawah;
use App\Models\LaporPanen;
use App\Models\SiklusTanam;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SiklusTanamController extends Controller
{
    private const ROLE_KELOMPOK_TANI = 1;
    private const ROLE_PETUGAS = 2;
    private const ROLE_BRIGADE_PANGAN = 5;

    public function index(Request $request)
    {
        [$userId, $roleId] = $this->authUser($request);
        if (!$this->rolePetani($roleId)) {
            return $this->forbidden('Akses aktivitas tanam tidak diizinkan.');
        }

        $query = SiklusTanam::with(['bibit', 'pupuk', 'lahan.pemilik', 'panen'])->orderByDesc('id');
        $this->batasiTanamUntukPetani($query, $userId, $roleId);

        return response()->json([
            'success' => true,
            'data' => $query->get()->map(fn (SiklusTanam $item) => $this->formatTanam($item, $userId, $roleId)),
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function store(Request $request)
    {
        $request->validate([
            'lahan_id' => 'required|integer',
            'bibit_id' => 'required|integer',
            'tanggal_tanam' => 'required|date|before_or_equal:today',
            'estimasi_hari_tanam' => 'nullable|integer|min:1|max:730',
            'pupuk_id' => 'required|integer',
            'tanggal_pemupukan' => 'required|date|before_or_equal:today',
            'takaran' => 'required|numeric|min:0.01',
        ]);

        [$userId, $roleId] = $this->authUser($request);
        if (!$this->rolePetani($roleId)) {
            return $this->forbidden('Hanya Kelompok Tani dan Brigade Pangan yang dapat membuat laporan tanam.');
        }

        $lahan = $this->lahanTanamYangDiizinkan($userId, $roleId, (int) $request->lahan_id);
        if (!$lahan) {
            return $this->forbidden('Lahan tidak terdaftar untuk petani ini atau belum disetujui.');
        }

        $aturan = $this->validasiAturanTanam($roleId, (int) $request->bibit_id, $request->tanggal_tanam);
        if ($aturan['error']) {
            return response()->json(['success' => false, 'message' => $aturan['error']], 422);
        }

        if (SiklusTanam::where('lahan_id', $lahan->id)->where('status_aktif', 'AKTIF')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Lahan masih memiliki proses tanam aktif. Selesaikan proses panen terlebih dahulu.',
            ], 422);
        }

        $bibit = $aturan['bibit'];
        $tanggalTanam = Carbon::parse($request->tanggal_tanam);
        $tanggalPemupukan = Carbon::parse($request->tanggal_pemupukan);
        if ($tanggalPemupukan->lt($tanggalTanam)) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal pemupukan tidak boleh sebelum tanggal tanam.',
            ], 422);
        }

        $pupuk = DB::table('jenis_pupuk')->where('id', $request->pupuk_id)->first();
        if (!$pupuk) {
            return response()->json(['success' => false, 'message' => 'Jenis pupuk tidak ditemukan.'], 422);
        }

        $estimasiHari = (int) ($request->input('estimasi_hari_tanam') ?: $bibit->masa_tanam_hari);

        $data = DB::transaction(function () use (
            $lahan,
            $bibit,
            $userId,
            $tanggalTanam,
            $tanggalPemupukan,
            $estimasiHari,
            $pupuk,
            $request
        ) {
            $tanam = SiklusTanam::create([
                'lahan_id' => $lahan->id,
                'bibit_id' => $bibit->id,
                'pupuk_id' => $pupuk->id,
                'petani_id' => $userId,
                'tanggal_tanam' => $tanggalTanam->toDateString(),
                'tanggal_pemupukan' => $tanggalPemupukan->toDateString(),
                'takaran_pupuk_kg' => $request->takaran,
                'pemupukan_dicatat_oleh' => $userId,
                'pemupukan_dicatat_at' => now(),
                'estimasi_hari' => $estimasiHari,
                'estimasi_tanggal_panen' => $tanggalTanam->copy()->addDays($estimasiHari)->toDateString(),
                'status_aktif' => 'AKTIF',
                'status_verifikasi' => 'PENDING',
            ]);

            DB::table('lahan_sawah')->where('id', $lahan->id)->update([
                'petani_id' => $userId,
                'updated_at' => now(),
            ]);

            return $tanam;
        });

        return response()->json([
            'success' => true,
            'message' => 'Laporan tanam dan pemupukan awal berhasil disimpan bersamaan.',
            'data' => $this->formatTanam($data->load(['bibit', 'pupuk', 'lahan.pemilik', 'panen']), $userId, $roleId),
        ], 201, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function storeLaporPanen(Request $request)
    {
        $request->validate([
            'siklus_tanam_id' => 'required|integer',
            'tanggal_panen' => 'required|date|before_or_equal:today',
            'hasil_panen' => 'required|numeric|min:0.01',
        ]);

        [$userId, $roleId] = $this->authUser($request);
        if ($roleId !== self::ROLE_KELOMPOK_TANI) {
            return $this->forbidden('Laporan hasil panen hanya dapat dibuat oleh Kelompok Tani sebagai pemilik lahan.');
        }

        $tanam = SiklusTanam::with(['lahan', 'bibit'])
            ->where('id', $request->siklus_tanam_id)
            ->where('status_aktif', 'AKTIF')
            ->whereHas('lahan', fn ($query) => $query->where('pemilik_id', $userId))
            ->first();

        if (!$tanam) {
            return $this->forbidden('Data tanam tidak ditemukan, sudah selesai, atau lahan bukan milik Anda.');
        }

        $tanggalPanen = Carbon::parse($request->tanggal_panen);
        if ($tanam->estimasi_tanggal_panen && $tanggalPanen->lt(Carbon::parse($tanam->estimasi_tanggal_panen))) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan panen belum dapat dibuat sebelum estimasi panen '
                    . Carbon::parse($tanam->estimasi_tanggal_panen)->format('d-m-Y') . '.',
            ], 422);
        }

        $laporan = LaporPanen::where('tanam_padi_id', $tanam->id)->first();
        if ($laporan && $laporan->status_verifikasi !== 'DITOLAK') {
            return response()->json([
                'success' => false,
                'message' => 'Proses tanam ini sudah memiliki laporan panen yang sedang atau telah diverifikasi.',
            ], 422);
        }

        $luas = (float) $tanam->lahan->luas_lahan_hektar;
        $hasil = (float) $request->hasil_panen;
        $payload = [
            'tanam_padi_id' => $tanam->id,
            'lahan_id' => $tanam->lahan_id,
            'bibit_id' => $tanam->bibit_id,
            'pemilik_id' => $userId,
            'petani_id' => $tanam->petani_id,
            'diverifikasi_oleh' => null,
            'nama_lahan' => $tanam->lahan->nama_lahan,
            'nama_bibit' => $tanam->bibit->nama_bibit,
            'varietas' => $tanam->bibit->varietas,
            'tanggal_tanam' => $tanam->tanggal_tanam,
            'tanggal_panen' => $tanggalPanen->toDateString(),
            'hasil_panen_ton' => $hasil,
            'luas_lahan_ha' => $luas,
            'produktivitas_ton_ha' => $luas > 0 ? round($hasil / $luas, 2) : 0,
            'status_verifikasi' => 'PENDING',
            'catatan_verifikasi' => null,
            'diverifikasi_at' => null,
        ];

        $laporan = $laporan
            ? tap($laporan)->update($payload)
            : LaporPanen::create($payload);

        $this->buatNotifikasiPetugas(
            'Laporan Panen Baru',
            'Kelompok Tani mengirim laporan panen untuk lahan ' . $tanam->lahan->nama_lahan . '.',
            'panen_padi',
            (int) $laporan->id,
            '/verifikasi-data-petani?tipe=panen&id=' . $laporan->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Laporan panen berhasil dikirim dan menunggu verifikasi petugas.',
            'data' => $this->ambilDetailHasilPanen((int) $laporan->id),
        ], $laporan->wasRecentlyCreated ? 201 : 200);
    }

    public function updateLaporPanen(Request $request, $id)
    {
        $request->validate([
            'tanggal_panen' => 'required|date|before_or_equal:today',
            'hasil_panen' => 'required|numeric|min:0.01',
        ]);

        [$userId, $roleId] = $this->authUser($request);
        if ($roleId !== self::ROLE_KELOMPOK_TANI) {
            return $this->forbidden('Perbaikan laporan panen hanya dapat dilakukan Kelompok Tani.');
        }

        $laporan = LaporPanen::with('siklusTanam')
            ->where('id', $id)
            ->where('pemilik_id', $userId)
            ->where('status_verifikasi', 'DITOLAK')
            ->first();
        if (!$laporan) {
            return response()->json(['success' => false, 'message' => 'Laporan panen ditolak tidak ditemukan.'], 404);
        }

        $tanggalPanen = Carbon::parse($request->tanggal_panen);
        if ($laporan->siklusTanam?->estimasi_tanggal_panen
            && $tanggalPanen->lt(Carbon::parse($laporan->siklusTanam->estimasi_tanggal_panen))) {
            return response()->json(['success' => false, 'message' => 'Tanggal panen masih sebelum estimasi panen.'], 422);
        }

        $hasil = (float) $request->hasil_panen;
        $luas = (float) $laporan->luas_lahan_ha;
        $laporan->update([
            'tanggal_panen' => $tanggalPanen->toDateString(),
            'hasil_panen_ton' => $hasil,
            'produktivitas_ton_ha' => $luas > 0 ? round($hasil / $luas, 2) : 0,
            'status_verifikasi' => 'PENDING',
            'catatan_verifikasi' => null,
            'diverifikasi_oleh' => null,
            'diverifikasi_at' => null,
        ]);

        $this->buatNotifikasiPetugas(
            'Perbaikan Laporan Panen',
            'Kelompok Tani mengajukan ulang laporan panen yang telah diperbaiki.',
            'panen_padi',
            (int) $laporan->id,
            '/verifikasi-data-petani?tipe=panen&id=' . $laporan->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Laporan panen berhasil diperbaiki dan diajukan ulang.',
            'data' => $this->ambilDetailHasilPanen((int) $laporan->id),
        ]);
    }

    public function showLaporPanen(Request $request, $id)
    {
        [$userId, $roleId] = $this->authUser($request);
        $query = $this->queryHasilPanen()->where('pp.id', $id);
        if ($roleId === self::ROLE_KELOMPOK_TANI) {
            $query->where('pp.pemilik_id', $userId);
        } elseif ($roleId === self::ROLE_BRIGADE_PANGAN) {
            $query->where('pp.petani_id', $userId);
        } elseif ($roleId !== self::ROLE_PETUGAS) {
            return $this->forbidden('Detail laporan panen tidak dapat diakses.');
        }

        $row = $query->first();
        return $row
            ? response()->json(['success' => true, 'data' => $this->formatHasilPanen($row)])
            : response()->json(['success' => false, 'message' => 'Laporan panen tidak ditemukan.'], 404);
    }

    public function show(Request $request, $id)
    {
        [$userId, $roleId] = $this->authUser($request);
        $query = SiklusTanam::with(['bibit', 'pupuk', 'lahan.pemilik', 'panen'])->where('id', $id);
        $this->batasiTanamUntukPetani($query, $userId, $roleId);
        $data = $query->first();

        return $data
            ? response()->json(['success' => true, 'data' => $this->formatTanam($data, $userId, $roleId)])
            : response()->json(['success' => false, 'message' => 'Data tanam tidak ditemukan.'], 404);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'lahan_id' => 'required|integer',
            'bibit_id' => 'required|integer',
            'tanggal_tanam' => 'required|date|before_or_equal:today',
            'estimasi_hari_tanam' => 'nullable|integer|min:1|max:730',
            'pupuk_id' => 'required|integer',
            'tanggal_pemupukan' => 'required|date|before_or_equal:today',
            'takaran' => 'required|numeric|min:0.01',
        ]);

        [$userId, $roleId] = $this->authUser($request);
        if (!$this->rolePetani($roleId)) {
            return $this->forbidden('Anda tidak memiliki akses untuk mengubah laporan tanam.');
        }

        $data = SiklusTanam::where('id', $id)->where('petani_id', $userId)->first();
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tanam tidak ditemukan.'], 404);
        }
        if ($data->status_aktif === 'NONAKTIF' || LaporPanen::where('tanam_padi_id', $id)->where('status_verifikasi', 'DITERIMA')->exists()) {
            return response()->json(['success' => false, 'message' => 'Data tanam yang telah dipanen tidak boleh diubah.'], 400);
        }

        $lahan = $this->lahanTanamYangDiizinkan($userId, $roleId, (int) $request->lahan_id);
        $aturan = $this->validasiAturanTanam($roleId, (int) $request->bibit_id, $request->tanggal_tanam);
        if (!$lahan || $aturan['error']) {
            return response()->json(['success' => false, 'message' => $aturan['error'] ?: 'Lahan tidak tersedia.'], 422);
        }

        $tanggalTanam = Carbon::parse($request->tanggal_tanam);
        $tanggalPemupukan = Carbon::parse($request->tanggal_pemupukan);
        if ($tanggalPemupukan->lt($tanggalTanam)) {
            return response()->json(['success' => false, 'message' => 'Tanggal pemupukan tidak boleh sebelum tanggal tanam.'], 422);
        }

        $pupuk = DB::table('jenis_pupuk')->where('id', $request->pupuk_id)->first();
        if (!$pupuk) {
            return response()->json(['success' => false, 'message' => 'Jenis pupuk tidak ditemukan.'], 422);
        }

        $estimasiHari = (int) ($request->input('estimasi_hari_tanam') ?: $aturan['bibit']->masa_tanam_hari);
        DB::transaction(function () use ($data, $lahan, $aturan, $tanggalTanam, $tanggalPemupukan, $estimasiHari, $pupuk, $request, $userId) {
            $data->update([
                'lahan_id' => $lahan->id,
                'bibit_id' => $aturan['bibit']->id,
                'pupuk_id' => $pupuk->id,
                'tanggal_tanam' => $tanggalTanam->toDateString(),
                'tanggal_pemupukan' => $tanggalPemupukan->toDateString(),
                'takaran_pupuk_kg' => $request->takaran,
                'pemupukan_dicatat_oleh' => $userId,
                'pemupukan_dicatat_at' => now(),
                'estimasi_hari' => $estimasiHari,
                'estimasi_tanggal_panen' => $tanggalTanam->copy()->addDays($estimasiHari)->toDateString(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Laporan tanam berhasil diperbarui.',
            'data' => $this->formatTanam($data->fresh(['bibit', 'pupuk', 'lahan.pemilik', 'panen']), $userId, $roleId),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        [$userId, $roleId] = $this->authUser($request);
        if (!$this->rolePetani($roleId)) {
            return $this->forbidden('Anda tidak memiliki akses untuk menghapus laporan tanam.');
        }

        $data = SiklusTanam::where('id', $id)->where('petani_id', $userId)->first();
        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tanam tidak ditemukan.'], 404);
        }
        if ($data->status_aktif === 'NONAKTIF' || LaporPanen::where('tanam_padi_id', $id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Data tanam yang memiliki laporan panen tidak boleh dihapus.'], 400);
        }

        $data->delete();
        return response()->json(['success' => true, 'message' => 'Data tanam berhasil dihapus.']);
    }

    public function totalProduksi(Request $request)
    {
        [$userId, $roleId] = $this->authUser($request);
        $query = LaporPanen::where('status_verifikasi', 'DITERIMA')
            ->whereYear('tanggal_panen', now()->year)
            ->whereDate('tanggal_panen', '<=', now()->toDateString());
        if ($roleId === self::ROLE_KELOMPOK_TANI) {
            $query->where('pemilik_id', $userId);
        } elseif ($roleId === self::ROLE_BRIGADE_PANGAN) {
            $query->where('petani_id', $userId);
        } else {
            return $this->forbidden('Ringkasan produksi hanya tersedia untuk petani.');
        }

        return response()->json([
            'success' => true,
            'data' => ['tahun' => now()->year, 'total_produksi' => (float) $query->sum('hasil_panen_ton')],
        ]);
    }

    public function getPendingVerifications(Request $request)
    {
        [, $roleId] = $this->authUser($request);
        if ($roleId !== self::ROLE_PETUGAS) {
            return $this->forbidden('Antrean verifikasi hanya dapat diakses petugas.');
        }

        $data = $this->queryHasilPanen()
            ->where('pp.status_verifikasi', 'PENDING')
            ->orderByDesc('pp.created_at')
            ->get()
            ->map(fn ($row) => $this->formatHasilPanen($row));

        return response()->json(['success' => true, 'data' => $data]);
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

        return response()->json(['success' => false, 'message' => 'Aksi verifikasi tidak valid.'], 422);
    }

    public function approve(Request $request, $id)
    {
        [$petugasId, $roleId] = $this->authUser($request);
        if ($roleId !== self::ROLE_PETUGAS) {
            return $this->forbidden('Verifikasi hasil panen hanya dapat dilakukan petugas.');
        }

        $result = DB::transaction(function () use ($id, $petugasId) {
            $panen = LaporPanen::where('id', $id)->lockForUpdate()->first();
            if (!$panen) {
                return ['status' => 404, 'body' => ['success' => false, 'message' => 'Data hasil panen tidak ditemukan.']];
            }
            if ($panen->status_verifikasi === 'DITERIMA') {
                return ['status' => 409, 'body' => ['success' => false, 'message' => 'Laporan panen sudah pernah disetujui.']];
            }

            $panen->update([
                'status_verifikasi' => 'DITERIMA',
                'catatan_verifikasi' => null,
                'diverifikasi_oleh' => $petugasId,
                'diverifikasi_at' => now(),
            ]);

            SiklusTanam::where('id', $panen->tanam_padi_id)->update([
                'status_aktif' => 'NONAKTIF',
                'status_verifikasi' => 'DITERIMA',
                'diverifikasi_oleh' => $petugasId,
                'diverifikasi_at' => now(),
                'catatan_verifikasi' => null,
            ]);

            $this->sinkronkanInfoLahanDariPanenTerakhir((int) $panen->lahan_id);
            $this->tandaiNotifikasiPanenTerbaca((int) $panen->id);

            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'message' => 'Laporan panen berhasil disetujui dan menjadi riwayat panen.',
                    'data' => $this->ambilDetailHasilPanen((int) $panen->id),
                ],
            ];
        });

        return response()->json($result['body'], $result['status'], [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    public function reject(Request $request, $id)
    {
        [$petugasId, $roleId] = $this->authUser($request);
        if ($roleId !== self::ROLE_PETUGAS) {
            return $this->forbidden('Verifikasi hasil panen hanya dapat dilakukan petugas.');
        }

        $request->validate(['alasan_penolakan' => 'nullable|string|max:1000']);
        $panen = LaporPanen::find($id);
        if (!$panen) {
            return response()->json(['success' => false, 'message' => 'Data hasil panen tidak ditemukan.'], 404);
        }
        if ($panen->status_verifikasi === 'DITERIMA') {
            return response()->json(['success' => false, 'message' => 'Hasil panen yang sudah diterima tidak boleh ditolak ulang.'], 400);
        }

        $panen->update([
            'status_verifikasi' => 'DITOLAK',
            'catatan_verifikasi' => $request->input('alasan_penolakan') ?: $request->input('catatan_verifikasi'),
            'diverifikasi_oleh' => $petugasId,
            'diverifikasi_at' => now(),
        ]);
        $this->tandaiNotifikasiPanenTerbaca((int) $panen->id);

        return response()->json([
            'success' => true,
            'message' => 'Laporan panen berhasil ditolak.',
            'data' => $this->ambilDetailHasilPanen((int) $panen->id),
        ]);
    }

    public function getJenisPupuk()
    {
        $data = DB::table('jenis_pupuk')->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'nama_pupuk' => $this->safeText($row->nama_bibit ?? $row->nama_pupuk ?? '-'),
            'tipe_pupuk' => $this->safeText($row->varietas ?? $row->tipe_pupuk ?? null),
        ]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getMySiklusTanam(Request $request)
    {
        return $this->index($request);
    }

    public function storeSiklusPupuk(Request $request)
    {
        $request->validate([
            'siklus_tanam_id' => 'required|integer',
            'pupuk_id' => 'required|integer',
            'tanggal_pemupukan' => 'required|date|before_or_equal:today',
            'takaran' => 'required|numeric|min:0.01',
        ]);

        [$userId, $roleId] = $this->authUser($request);
        if (!$this->rolePetani($roleId)) {
            return $this->forbidden('Catatan pemupukan hanya tersedia untuk petani.');
        }

        $query = SiklusTanam::with('lahan')->where('id', $request->siklus_tanam_id)->where('status_aktif', 'AKTIF');
        $this->batasiTanamUntukPetani($query, $userId, $roleId);
        $tanam = $query->first();
        if (!$tanam) {
            return $this->forbidden('Data tanam tidak valid atau sudah selesai.');
        }

        $tanggal = Carbon::parse($request->tanggal_pemupukan);
        if ($tanggal->lt(Carbon::parse($tanam->tanggal_tanam))) {
            return response()->json(['success' => false, 'message' => 'Tanggal pemupukan tidak boleh sebelum tanggal tanam.'], 422);
        }

        $pupuk = DB::table('jenis_pupuk')->where('id', $request->pupuk_id)->first();
        if (!$pupuk) {
            return response()->json(['success' => false, 'message' => 'Jenis pupuk tidak ditemukan.'], 422);
        }

        $entry = [
            'id' => (int) $tanam->id,
            'pupuk_id' => (int) $pupuk->id,
            'nama_pupuk' => $pupuk->nama_bibit ?? $pupuk->nama_pupuk ?? '-',
            'tipe_pupuk' => $pupuk->varietas ?? $pupuk->tipe_pupuk ?? null,
            'tanggal_pemupukan' => $tanggal->toDateString(),
            'takaran' => (float) $request->takaran,
            'dicatat_oleh' => $userId,
            'dicatat_at' => now()->toIso8601String(),
        ];
        $tanam->update([
            'pupuk_id' => $pupuk->id,
            'tanggal_pemupukan' => $tanggal->toDateString(),
            'takaran_pupuk_kg' => $request->takaran,
            'pemupukan_dicatat_oleh' => $userId,
            'pemupukan_dicatat_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rincian pemupukan pada data tanam berhasil diperbarui.',
            'data' => array_merge($entry, ['siklus_tanam_id' => (int) $tanam->id]),
        ]);
    }

    public function getSiklusPupuk(Request $request)
    {
        [$userId, $roleId] = $this->authUser($request);
        if (!$this->rolePetani($roleId)) {
            return $this->forbidden('Riwayat pemupukan hanya tersedia untuk petani.');
        }

        $query = SiklusTanam::with(['lahan', 'pupuk'])->orderByDesc('tanggal_pemupukan')->orderByDesc('id');
        $this->batasiTanamUntukPetani($query, $userId, $roleId);
        $riwayat = $query->get()->map(function (SiklusTanam $tanam) {
            return [
                'id' => (int) $tanam->id,
                'siklus_tanam_id' => (int) $tanam->id,
                'nama_lahan' => $tanam->lahan?->nama_lahan ?? '-',
                'nama_pupuk' => $tanam->pupuk?->nama_bibit ?? $tanam->pupuk?->nama_pupuk ?? '-',
                'tipe_pupuk' => $tanam->pupuk?->varietas ?? $tanam->pupuk?->tipe_pupuk,
                'tanggal_pemupukan' => optional($tanam->tanggal_pemupukan)->format('Y-m-d'),
                'takaran' => (float) $tanam->takaran_pupuk_kg,
            ];
        })->values();

        $perPage = max(1, (int) $request->input('per_page', 3));
        $page = max(1, (int) $request->input('pupuk_page', 1));
        $paginator = new LengthAwarePaginator(
            $riwayat->forPage($page, $perPage)->values(),
            $riwayat->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'pageName' => 'pupuk_page']
        );

        return response()->json(['success' => true, 'data' => $paginator]);
    }

    private function queryHasilPanen()
    {
        return DB::table('panen_padi as pp')
            ->join('tanam_padi as tp', 'tp.id', '=', 'pp.tanam_padi_id')
            ->join('lahan_sawah as ls', 'ls.id', '=', 'pp.lahan_id')
            ->leftJoin('users as pemilik', 'pemilik.id', '=', 'pp.pemilik_id')
            ->leftJoin('users as petani', 'petani.id', '=', 'pp.petani_id')
            ->leftJoin('jenis_bibit as jb', 'jb.id', '=', 'pp.bibit_id')
            ->leftJoin('kecamatan as kc', 'kc.id', '=', 'ls.kecamatan_id')
            ->leftJoin('kelurahan as kl', 'kl.id', '=', 'ls.kelurahan_id')
            ->select([
                'pp.*',
                'tp.estimasi_hari',
                'tp.estimasi_tanggal_panen',
                'tp.status_aktif',
                'ls.hasil_panen_ton as lahan_hasil_panen_ton',
                'ls.produktivitas_ton_ha as lahan_produktivitas_ton_ha',
                'ls.alamat_detail',
                'ls.latitude',
                'ls.longitude',
                'pemilik.nama_lengkap as nama_pemilik',
                'pemilik.email as email_pemilik',
                'pemilik.no_hp as no_hp_pemilik',
                'petani.nama_lengkap as nama_petani',
                'jb.masa_tanam_hari',
                'kc.nama_kecamatan',
                'kl.nama_kelurahan',
            ]);
    }

    private function ambilDetailHasilPanen(int $id): ?array
    {
        $row = $this->queryHasilPanen()->where('pp.id', $id)->first();
        return $row ? $this->formatHasilPanen($row) : null;
    }

    private function formatHasilPanen($row): array
    {
        return [
            'id' => (int) $row->id,
            'siklus_tanam_id' => (int) $row->tanam_padi_id,
            'tanam_padi_id' => (int) $row->tanam_padi_id,
            'lahan_id' => (int) $row->lahan_id,
            'bibit_id' => (int) $row->bibit_id,
            'tanggal_tanam' => $row->tanggal_tanam,
            'estimasi_panen' => (int) ($row->estimasi_hari ?? 0),
            'status_aktif' => $row->status_aktif,
            'tanggal_panen' => $row->tanggal_panen,
            'hasil_panen' => (float) $row->hasil_panen_ton,
            'hasil_panen_ton' => (float) $row->hasil_panen_ton,
            'hasil_panen_label' => number_format((float) $row->hasil_panen_ton, 2, ',', '.') . ' Ton',
            'status_verifikasi' => $row->status_verifikasi,
            'catatan_verifikasi' => $this->safeText($row->catatan_verifikasi),
            'created_by' => (int) $row->pemilik_id,
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
            'nama_petani' => $this->safeText($row->nama_pemilik),
            'email_petani' => $this->safeText($row->email_pemilik),
            'no_hp_petani' => $this->safeText($row->no_hp_pemilik),
            'nama_penggarap' => $this->safeText($row->nama_petani),
            'penggarap_user_id' => (int) $row->petani_id,
            'nama_bibit' => $this->safeText($row->nama_bibit),
            'varietas' => $this->safeText($row->varietas),
            'masa_tanam_hari' => (int) ($row->masa_tanam_hari ?? $row->estimasi_hari ?? 0),
            'nama_lahan' => $this->safeText($row->nama_lahan),
            'pemilik_lahan' => $this->safeText($row->nama_pemilik),
            'luas_lahan_hektar' => (float) $row->luas_lahan_ha,
            'produktivitas_pengajuan_ton_ha' => (float) $row->produktivitas_ton_ha,
            'lahan_hasil_panen_ton' => (float) ($row->lahan_hasil_panen_ton ?? 0),
            'lahan_produktivitas_ton_ha' => (float) ($row->lahan_produktivitas_ton_ha ?? 0),
            'alamat_detail' => $this->safeText($row->alamat_detail),
            'latitude' => $row->latitude !== null ? (float) $row->latitude : null,
            'longitude' => $row->longitude !== null ? (float) $row->longitude : null,
            'nama_kecamatan' => $this->safeText($row->nama_kecamatan),
            'nama_kelurahan' => $this->safeText($row->nama_kelurahan),
            'petani' => [
                'id' => (int) $row->pemilik_id,
                'nama_lengkap' => $this->safeText($row->nama_pemilik),
                'email' => $this->safeText($row->email_pemilik),
                'no_hp' => $this->safeText($row->no_hp_pemilik),
            ],
            'lahan' => [
                'id' => (int) $row->lahan_id,
                'nama_lahan' => $this->safeText($row->nama_lahan),
                'pemilik_lahan' => $this->safeText($row->nama_pemilik),
                'luas_lahan_hektar' => (float) $row->luas_lahan_ha,
            ],
        ];
    }

    private function sinkronkanInfoLahanDariPanenTerakhir(int $lahanId): void
    {
        $panen = LaporPanen::where('lahan_id', $lahanId)
            ->where('status_verifikasi', 'DITERIMA')
            ->whereDate('tanggal_panen', '<=', now()->toDateString())
            ->orderByDesc('tanggal_panen')
            ->orderByDesc('id')
            ->first();
        if (!$panen) {
            return;
        }

        DB::table('lahan_sawah')->where('id', $lahanId)->update([
            'hasil_panen_ton' => $panen->hasil_panen_ton,
            'produktivitas_ton_ha' => $panen->produktivitas_ton_ha,
            'panen_terakhir_id' => $panen->id,
            'updated_at' => now(),
        ]);
    }

    private function batasiTanamUntukPetani($query, int $userId, int $roleId): void
    {
        if ($roleId === self::ROLE_KELOMPOK_TANI) {
            $query->whereHas('lahan', fn ($q) => $q->where('pemilik_id', $userId));
        } elseif ($roleId === self::ROLE_BRIGADE_PANGAN) {
            $query->where('petani_id', $userId);
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    private function lahanTanamYangDiizinkan(int $userId, int $roleId, int $lahanId): ?LahanSawah
    {
        $query = LahanSawah::where('id', $lahanId)->where('status_verifikasi', 'DITERIMA');
        if ($roleId === self::ROLE_KELOMPOK_TANI) {
            return $query->where(function ($q) use ($userId) {
                $q->where('pemilik_id', $userId)->orWhere('petani_id', $userId);
            })->first();
        }
        if ($roleId === self::ROLE_BRIGADE_PANGAN) {
            return $query->where('petani_id', $userId)->first();
        }
        return null;
    }

    private function validasiAturanTanam(int $roleId, int $bibitId, string $tanggalTanam): array
    {
        $bibit = DB::table('jenis_bibit')->where('id', $bibitId)->first();
        if (!$bibit) {
            return ['error' => 'Jenis bibit tidak ditemukan.', 'bibit' => null];
        }

        $bulan = (int) Carbon::parse($tanggalTanam)->format('n');
        $varietas = mb_strtolower((string) $bibit->varietas);
        if ($roleId === self::ROLE_KELOMPOK_TANI && ($varietas !== 'lokal' || $bulan < 1 || $bulan > 9)) {
            return ['error' => 'Kelompok Tani menggunakan bibit lokal pada Januari sampai September.', 'bibit' => $bibit];
        }
        if ($roleId === self::ROLE_BRIGADE_PANGAN
            && ($varietas !== 'unggul' || !in_array($bulan, [10, 11, 12, 1], true))) {
            return ['error' => 'Brigade Pangan menggunakan bibit unggul pada Oktober sampai Januari.', 'bibit' => $bibit];
        }

        return ['error' => null, 'bibit' => $bibit];
    }

    private function formatTanam(SiklusTanam $item, int $userId, int $roleId): array
    {
        $tanggalTanam = Carbon::parse($item->tanggal_tanam);
        $estimasi = $item->estimasi_tanggal_panen
            ? Carbon::parse($item->estimasi_tanggal_panen)
            : $tanggalTanam->copy()->addDays((int) $item->estimasi_hari);
        $totalHari = max(1, $tanggalTanam->diffInDays($estimasi));
        $progress = (int) min(100, max(0, round((max(0, $tanggalTanam->diffInDays(now(), false)) / $totalHari) * 100)));
        $pemilik = $item->lahan?->pemilik?->nama_lengkap ?? '-';
        $pemupukanAwal = [
            'id' => (int) $item->id,
            'pupuk_id' => (int) $item->pupuk_id,
            'nama_pupuk' => $item->pupuk?->nama_bibit ?? $item->pupuk?->nama_pupuk ?? '-',
            'tipe_pupuk' => $item->pupuk?->varietas ?? $item->pupuk?->tipe_pupuk,
            'tanggal_pemupukan' => optional($item->tanggal_pemupukan)->format('Y-m-d'),
            'takaran' => (float) $item->takaran_pupuk_kg,
            'dicatat_oleh' => (int) $item->pemupukan_dicatat_oleh,
            'dicatat_at' => optional($item->pemupukan_dicatat_at)->toIso8601String(),
        ];

        return [
            'id' => (int) $item->id,
            'lahan_id' => (int) $item->lahan_id,
            'bibit_id' => (int) $item->bibit_id,
            'tanggal_tanam' => optional($item->tanggal_tanam)->format('Y-m-d'),
            'estimasi_panen' => (int) $item->estimasi_hari,
            'estimasi_tanggal_panen' => $estimasi->toDateString(),
            'status_aktif' => $item->status_aktif,
            'tanggal_panen' => optional($item->panen?->tanggal_panen)->format('Y-m-d'),
            'hasil_panen' => $item->panen?->hasil_panen_ton,
            'status_verifikasi' => $item->status_verifikasi,
            'peran_pelapor' => $roleId === self::ROLE_BRIGADE_PANGAN ? 'brigade_pangan' : 'kelompok_tani',
            'created_by' => (int) $item->petani_id,
            'petani_id' => (int) $item->petani_id,
            'nama_lahan' => $item->lahan?->nama_lahan ?? '-',
            'pemilik_lahan' => $pemilik,
            'nama_bibit' => $item->bibit?->nama_bibit ?? '-',
            'varietas' => $item->bibit?->varietas,
            'masa_tanam_hari' => (int) ($item->bibit?->masa_tanam_hari ?? $item->estimasi_hari),
            'progress_persen' => $progress,
            'hari_tersisa' => max(0, now()->startOfDay()->diffInDays($estimasi->startOfDay(), false)),
            'status_laporan_panen' => $item->panen?->status_verifikasi,
            'pemupukan_awal' => $pemupukanAwal,
            'pupuk_id' => $pemupukanAwal['pupuk_id'],
            'tanggal_pemupukan' => $pemupukanAwal['tanggal_pemupukan'],
            'takaran' => $pemupukanAwal['takaran'],
            'can_edit' => (int) $item->petani_id === $userId && $item->status_aktif === 'AKTIF' && !$item->panen,
            'can_delete' => (int) $item->petani_id === $userId && $item->status_aktif === 'AKTIF' && !$item->panen,
            'can_report_harvest' => $roleId === self::ROLE_KELOMPOK_TANI
                && (int) ($item->lahan?->pemilik_id ?? 0) === $userId
                && $item->status_aktif === 'AKTIF'
                && now()->startOfDay()->gte($estimasi->startOfDay())
                && (!$item->panen || $item->panen->status_verifikasi === 'DITOLAK'),
        ];
    }

    private function buatNotifikasiPetugas(string $judul, string $pesan, ?string $refType, ?int $refId, ?string $targetUrl): void
    {
        try {
            DB::table('notifikasi')->insert([
                'role_id_penerima' => self::ROLE_PETUGAS,
                'user_id_penerima' => null,
                'judul' => $judul,
                'pesan' => $pesan,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'target_url' => $targetUrl,
                'is_read' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $error) {
            Log::warning('Notifikasi panen gagal dibuat.', ['error' => $error->getMessage()]);
        }
    }

    private function tandaiNotifikasiPanenTerbaca(int $id): void
    {
        DB::table('notifikasi')
            ->where('ref_type', 'panen_padi')
            ->where('ref_id', $id)
            ->update(['is_read' => 1, 'updated_at' => now()]);
    }

    private function authUser(Request $request): array
    {
        $user = $request->attributes->get('auth');
        return [(int) ($user->sub ?? $user->id ?? 0), (int) ($user->role_id ?? $user->role ?? 0)];
    }

    private function rolePetani(int $roleId): bool
    {
        return in_array($roleId, [self::ROLE_KELOMPOK_TANI, self::ROLE_BRIGADE_PANGAN], true);
    }

    private function forbidden(string $message)
    {
        return response()->json(['success' => false, 'message' => $message], 403);
    }

    private function safeText($value): ?string
    {
        return $value === null ? null : mb_convert_encoding((string) $value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
    }
}
